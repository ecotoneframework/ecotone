<?php


namespace Ecotone\EventSourcing;


use Doctrine\DBAL\Driver\PDOConnection;
use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\EventSourcing\PersistenceStrategy\InterlopMariaDbSimpleStreamStrategy;
use Ecotone\EventSourcing\PersistenceStrategy\InterlopMysqlSimpleStreamStrategy;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Enqueue\Dbal\DbalConnectionFactory;
use Iterator;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Pdo\MariaDbEventStore;
use Prooph\EventStore\Pdo\MySqlEventStore;
use Prooph\EventStore\Pdo\PersistenceStrategy;
use Prooph\EventStore\Pdo\PostgresEventStore;
use Prooph\EventStore\Pdo\WriteLockStrategy\MariaDbMetadataLockStrategy;
use Prooph\EventStore\Pdo\WriteLockStrategy\MysqlMetadataLockStrategy;
use Prooph\EventStore\Pdo\WriteLockStrategy\NoLockStrategy;
use Prooph\EventStore\Pdo\WriteLockStrategy\PostgresAdvisoryLockStrategy;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;

class LazyProophEventStore implements EventStore
{
    const DEFAULT_ENABLE_WRITE_LOCK_STRATEGY = false;
    const INITIALIZE_ON_STARTUP = true;
    const LOAD_BATCH_SIZE = 1000;

    const DEFAULT_STREAM_TABLE = "event_streams";
    const DEFAULT_PROJECTIONS_TABLE = "projections";

    const EVENT_STORE_TYPE_MYSQL = "mysql";
    const EVENT_STORE_TYPE_POSTGRES = "postgres";
    const EVENT_STORE_TYPE_MARIADB = "mariadb";
    const EVENT_STORE_TYPE_IN_MEMORY = "inMemory";

    const SINGLE_STREAM_PERSISTENCE = "single";
    const AGGREGATE_STREAM_PERSISTENCE = "aggregate";
    const SIMPLE_STREAM_PERSISTENCE = "simple";
    const CUSTOM_STREAM_PERSISTENCE = "custom";

    const AGGREGATE_VERSION = '_aggregate_version';
    const AGGREGATE_TYPE = '_aggregate_type';
    const AGGREGATE_ID = '_aggregate_id';

    private ?EventStore $initializedEventStore = null;
    private ReferenceSearchService $referenceSearchService;
    private MessageFactory $messageFactory;
    private MessageConverter $messageConverter;
    private bool $requireInitialization;
    private array $ensuredExistingStreams = [];
    private EventSourcingConfiguration $eventSourcingConfiguration;

    public function __construct(EventSourcingConfiguration $eventSourcingConfiguration, ReferenceSearchService $referenceSearchService)
    {
        $this->messageConverter = new FromProophMessageToArrayConverter();
        $this->messageFactory = $referenceSearchService->get(EventMapper::class);
        $this->eventSourcingConfiguration = $eventSourcingConfiguration;
        $this->requireInitialization = $eventSourcingConfiguration->isInitializedOnStart();
        $this->referenceSearchService = $referenceSearchService;
    }

    public function fetchStreamMetadata(StreamName $streamName): array
    {
        return $this->getEventStore()->fetchStreamMetadata($streamName);
    }

    public function hasStream(StreamName $streamName): bool
    {
        return $this->getEventStore()->hasStream($streamName);
    }

    public function load(StreamName $streamName, int $fromNumber = 1, int $count = null, MetadataMatcher $metadataMatcher = null): Iterator
    {
        return $this->getEventStore()->load($streamName, $fromNumber, $count, $metadataMatcher);
    }

    public function loadReverse(StreamName $streamName, int $fromNumber = null, int $count = null, MetadataMatcher $metadataMatcher = null): Iterator
    {
        return $this->getEventStore()->loadReverse($streamName, $fromNumber, $count, $metadataMatcher);
    }

    public function fetchStreamNames(?string $filter, ?MetadataMatcher $metadataMatcher, int $limit = 20, int $offset = 0): array
    {
        return $this->getEventStore()->fetchStreamNames($filter, $metadataMatcher, $limit, $offset);
    }

    public function fetchStreamNamesRegex(string $filter, ?MetadataMatcher $metadataMatcher, int $limit = 20, int $offset = 0): array
    {
        return $this->getEventStore()->fetchStreamNamesRegex($filter, $metadataMatcher, $limit, $offset);
    }

    public function fetchCategoryNames(?string $filter, int $limit = 20, int $offset = 0): array
    {
        return $this->getEventStore()->fetchCategoryNames($filter, $limit, $offset);
    }

    public function fetchCategoryNamesRegex(string $filter, int $limit = 20, int $offset = 0): array
    {
        return $this->getEventStore()->fetchCategoryNamesRegex($filter, $limit, $offset);
    }

    public function updateStreamMetadata(StreamName $streamName, array $newMetadata): void
    {
        $this->getEventStore()->updateStreamMetadata($streamName, $newMetadata);
    }

    public function create(Stream $stream): void
    {
        $this->getEventStore()->create($stream);
        $this->ensuredExistingStreams[$stream->streamName()->toString()] = true;
    }

    public function appendTo(StreamName $streamName, Iterator $streamEvents): void
    {
        if (!array_key_exists($streamName->toString(), $this->ensuredExistingStreams) && !$this->hasStream($streamName)) {
            $this->create(new Stream($streamName, $streamEvents, []));
        }else {
            $this->getEventStore()->appendTo($streamName, $streamEvents);
        }
    }

    public function delete(StreamName $streamName): void
    {
        $this->getEventStore()->delete($streamName);
        unset($this->ensuredExistingStreams[$streamName->toString()]);
    }

    public function prepareEventStore() : void
    {
        if (!$this->requireInitialization || $this->eventSourcingConfiguration->isInMemory()) {
            return;
        }

        $sm = $this->getConnection()->getSchemaManager();
        if (!$sm->tablesExist([$this->eventSourcingConfiguration->getEventStreamTableName()])) {
            match ($this->getEventStoreType()) {
                self::EVENT_STORE_TYPE_POSTGRES => $this->createPostgresEventStreamTable(),
                self::EVENT_STORE_TYPE_MARIADB => $this->createMariadbEventStreamTable(),
                self::EVENT_STORE_TYPE_MYSQL => $this->createMysqlEventStreamTable()
            };
        }
        if (!$sm->tablesExist([$this->eventSourcingConfiguration->getProjectionsTable()])) {
            match ($this->getEventStoreType()) {
                self::EVENT_STORE_TYPE_POSTGRES => $this->createPostgresProjectionTable(),
                self::EVENT_STORE_TYPE_MARIADB => $this->createMariadbProjectionTable(),
                self::EVENT_STORE_TYPE_MYSQL => $this->createMysqlProjectionTable()
            };
        }

        $this->requireInitialization = false;
    }

    public function getEventStore() : EventStore
    {
        if ($this->initializedEventStore) {
            return $this->initializedEventStore;
        }
        $this->prepareEventStore();

        if ($this->eventSourcingConfiguration->isInMemory()) {
            $this->initializedEventStore = $this->eventSourcingConfiguration->getInMemoryEventStore();

            return $this->initializedEventStore;
        }

        $eventStoreType =  $this->getEventStoreType();

        $persistenceStrategy = match ($eventStoreType) {
            self::EVENT_STORE_TYPE_MYSQL => $this->getMysqlPersistenceStrategy(),
            self::EVENT_STORE_TYPE_MARIADB => $this->getMariaDbPersistenceStrategy(),
            self::EVENT_STORE_TYPE_POSTGRES => $this->getPostgresPersistenceStrategy(),
            default => throw InvalidArgumentException::create('Unexpected match value ' . $eventStoreType)
        };

        $writeLockStrategy = new NoLockStrategy();
        $connection = $this->getWrappedConnection();
        if ($this->eventSourcingConfiguration->isWriteLockStrategyEnabled()) {
            $writeLockStrategy = match ($eventStoreType) {
                self::EVENT_STORE_TYPE_MYSQL => new MysqlMetadataLockStrategy($connection),
                self::EVENT_STORE_TYPE_MARIADB => new MariaDbMetadataLockStrategy($connection),
                self::EVENT_STORE_TYPE_POSTGRES => new PostgresAdvisoryLockStrategy($connection)
            };
        }

        $eventStoreClass = match ($eventStoreType) {
            self::EVENT_STORE_TYPE_MYSQL => MySqlEventStore::class,
            self::EVENT_STORE_TYPE_MARIADB => MariaDbEventStore::class,
            self::EVENT_STORE_TYPE_POSTGRES => PostgresEventStore::class
        };

        $eventStore = new $eventStoreClass(
            $this->messageFactory,
            $connection,
            $persistenceStrategy,
            $this->eventSourcingConfiguration->getLoadBatchSize(),
            $this->eventSourcingConfiguration->getEventStreamTableName(),
            true,
            $writeLockStrategy
        );

        $this->initializedEventStore = $eventStore;

        return $eventStore;
    }

    private function getMysqlPersistenceStrategy(): PersistenceStrategy
    {
        return match ($this->eventSourcingConfiguration->getPersistenceStrategy()) {
            self::AGGREGATE_STREAM_PERSISTENCE => new PersistenceStrategy\MySqlAggregateStreamStrategy($this->messageConverter),
            self::SINGLE_STREAM_PERSISTENCE => new PersistenceStrategy\MySqlSingleStreamStrategy($this->messageConverter),
            self::SIMPLE_STREAM_PERSISTENCE => new InterlopMysqlSimpleStreamStrategy($this->messageConverter),
            self::CUSTOM_STREAM_PERSISTENCE => $this->eventSourcingConfiguration->getCustomPersistenceStrategy(),
        };
    }

    private function getMariaDbPersistenceStrategy(): PersistenceStrategy
    {
        return match ($this->eventSourcingConfiguration->getPersistenceStrategy()) {
            self::AGGREGATE_STREAM_PERSISTENCE => new PersistenceStrategy\MariaDbAggregateStreamStrategy($this->messageConverter),
            self::SINGLE_STREAM_PERSISTENCE => new PersistenceStrategy\MariaDbSingleStreamStrategy($this->messageConverter),
            self::SIMPLE_STREAM_PERSISTENCE => new InterlopMariaDbSimpleStreamStrategy($this->messageConverter),
            self::CUSTOM_STREAM_PERSISTENCE => $this->eventSourcingConfiguration->getCustomPersistenceStrategy(),
        };
    }

    private function getPostgresPersistenceStrategy(): PersistenceStrategy
    {
        return match ($this->eventSourcingConfiguration->getPersistenceStrategy()) {
            self::AGGREGATE_STREAM_PERSISTENCE => new PersistenceStrategy\PostgresAggregateStreamStrategy($this->messageConverter),
            self::SINGLE_STREAM_PERSISTENCE => new PersistenceStrategy\PostgresSingleStreamStrategy($this->messageConverter),
            self::SIMPLE_STREAM_PERSISTENCE => new PersistenceStrategy\PostgresSimpleStreamStrategy($this->messageConverter),
            self::CUSTOM_STREAM_PERSISTENCE => $this->eventSourcingConfiguration->getCustomPersistenceStrategy(),
        };
    }

    public function getEventStoreType() : string
    {
        if ($this->eventSourcingConfiguration->isInMemory()) {
            return self::EVENT_STORE_TYPE_IN_MEMORY;
        }

        $connection = $this->getWrappedConnection();

        $eventStoreType = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($eventStoreType === self::EVENT_STORE_TYPE_MYSQL && str_contains($connection->getAttribute(\PDO::ATTR_SERVER_VERSION), "MariaDB")) {
            $eventStoreType = self::EVENT_STORE_TYPE_MARIADB;
        }
        if ($eventStoreType === "pgsql") {
            $eventStoreType = self::EVENT_STORE_TYPE_POSTGRES;
        }
        return $eventStoreType;
    }

    public function getConnection(): \Doctrine\DBAL\Connection
    {
        $connectionFactory = new DbalReconnectableConnectionFactory($this->referenceSearchService->get($this->eventSourcingConfiguration->getConnectionReferenceName()));

        return $connectionFactory->getConnection();
    }

    /** @phpstan-ignore-next-line */
    public function getWrappedConnection(): PDOConnection|\PDO
    {
        try {
            return $this->getConnection()->getNativeConnection();
        }catch (\LogicException) {
            /** Case when getNativeConnection is not implemented in nested connection */
            $connection = $this->getConnection()->getWrappedConnection();

            if ($connection instanceof \PDO || is_subclass_of($connection, "Doctrine\DBAL\Driver\PDOConnection") || get_class($connection) === "Doctrine\DBAL\Driver\PDOConnection") {
                return $connection;
            }

            /** Laravel case @look Illuminate\Database\PDO\Connection */
            return $connection->getWrappedConnection();
        }
    }

    private function createMysqlEventStreamTable() : void
    {
        $this->getConnection()->executeStatement(<<<SQL
    CREATE TABLE `event_streams` (
  `no` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `real_stream_name` VARCHAR(150) NOT NULL,
  `stream_name` CHAR(41) NOT NULL,
  `metadata` JSON,
  `category` VARCHAR(150),
  PRIMARY KEY (`no`),
  UNIQUE KEY `ix_rsn` (`real_stream_name`),
  KEY `ix_cat` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
SQL);
    }

    private function createMariadbEventStreamTable() : void
    {
        $this->getConnection()->executeStatement(<<<SQL
CREATE TABLE `event_streams` (
    `no` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `real_stream_name` VARCHAR(150) NOT NULL,
    `stream_name` CHAR(41) NOT NULL,
    `metadata` LONGTEXT NOT NULL,
    `category` VARCHAR(150),
    CHECK (`metadata` IS NOT NULL OR JSON_VALID(`metadata`)),
    PRIMARY KEY (`no`),
    UNIQUE KEY `ix_rsn` (`real_stream_name`),
    KEY `ix_cat` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
SQL);
    }

    private function createPostgresEventStreamTable() : void
    {
        $this->getConnection()->executeStatement(<<<SQL
CREATE TABLE event_streams (
  no BIGSERIAL,
  real_stream_name VARCHAR(150) NOT NULL,
  stream_name CHAR(41) NOT NULL,
  metadata JSONB,
  category VARCHAR(150),
  PRIMARY KEY (no),
  UNIQUE (stream_name)
);
CREATE INDEX on event_streams (category);
SQL);
    }

    private function createMysqlProjectionTable(): void
    {
        $this->getConnection()->executeStatement(<<<SQL
CREATE TABLE `projections` (
  `no` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `position` JSON,
  `state` JSON,
  `status` VARCHAR(28) NOT NULL,
  `locked_until` CHAR(26),
  PRIMARY KEY (`no`),
  UNIQUE KEY `ix_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
SQL
        );
    }

    private function createMariadbProjectionTable(): void
    {
        $this->getConnection()->executeStatement(<<<SQL
CREATE TABLE `projections` (
  `no` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `position` LONGTEXT,
  `state` LONGTEXT,
  `status` VARCHAR(28) NOT NULL,
  `locked_until` CHAR(26),
  CHECK (`position` IS NULL OR JSON_VALID(`position`)),
  CHECK (`state` IS NULL OR JSON_VALID(`state`)),
  PRIMARY KEY (`no`),
  UNIQUE KEY `ix_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
SQL
        );
    }

    private function createPostgresProjectionTable(): void
    {
        $this->getConnection()->executeStatement(<<<SQL
CREATE TABLE projections (
  no BIGSERIAL,
  name VARCHAR(150) NOT NULL,
  position JSONB,
  state JSONB,
  status VARCHAR(28) NOT NULL,
  locked_until CHAR(26),
  PRIMARY KEY (no),
  UNIQUE (name)
);
SQL
        );
    }

    /**
     * @param \Doctrine\DBAL\Driver\Connection|null $connection
     * @return bool
     */
    private function isDbalVersionThreeOrHigher(?\Doctrine\DBAL\Driver\Connection $connection): bool
    {
        return $connection instanceof \Doctrine\DBAL\Driver\PDO\Connection;
    }
}