<?php

namespace Ecotone\EventSourcing\Prooph\PersistenceStrategy;

use Iterator;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\EventStore\Pdo\DefaultMessageConverter;
use Prooph\EventStore\Pdo\PersistenceStrategy\MariaDbPersistenceStrategy;
use Prooph\EventStore\Pdo\Util\Json;
use Prooph\EventStore\StreamName;

use function sha1;

/**
 * Using Normal Simple Stream Strategy is using different index name.
 * This cause "Key 'ix_query_aggregate' doesn't exist in table" in case strategies are interloped
 */
final class InterlopMariaDbSimpleStreamStrategy implements MariaDbPersistenceStrategy
{
    /**
     * @var MessageConverter
     */
    private $messageConverter;

    public function __construct(?MessageConverter $messageConverter = null)
    {
        $this->messageConverter = $messageConverter ?? new DefaultMessageConverter();
    }

    /**
     * @param string $tableName
     * @return string[]
     */
    public function createSchema(string $tableName): array
    {
        $statement = <<<EOT
            CREATE TABLE `$tableName` (
                `no` BIGINT(20) NOT NULL AUTO_INCREMENT,
                `event_id` CHAR(36) COLLATE utf8mb4_bin NOT NULL,
                `event_name` VARCHAR(100) COLLATE utf8mb4_bin NOT NULL,
                `payload` LONGTEXT NOT NULL,
                `metadata` LONGTEXT NOT NULL,
                `created_at` DATETIME(6) NOT NULL,
                CHECK (`payload` IS NOT NULL AND JSON_VALID(`payload`)),
                CHECK (`metadata` IS NOT NULL AND JSON_VALID(`metadata`)),
                PRIMARY KEY (`no`),
                UNIQUE KEY `ix_query_aggregate` (`event_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
            EOT;

        return [$statement];
    }

    public function columnNames(): array
    {
        return [
            'event_id',
            'event_name',
            'payload',
            'metadata',
            'created_at',
        ];
    }

    public function prepareData(Iterator $streamEvents): array
    {
        $data = [];

        foreach ($streamEvents as $event) {
            $eventData = $this->messageConverter->convertToArray($event);

            $data[] = $eventData['uuid'];
            $data[] = $eventData['message_name'];
            $data[] = Json::encode($eventData['payload']);
            $data[] = Json::encode($eventData['metadata']);
            $data[] = $eventData['created_at']->format('Y-m-d\TH:i:s.u');
        }

        return $data;
    }

    public function generateTableName(StreamName $streamName): string
    {
        return '_' . sha1($streamName->toString());
    }
}
