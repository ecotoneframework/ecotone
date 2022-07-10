<?php


namespace Ecotone\Dbal\Deduplication;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\Clock;
use Enqueue\Dbal\DbalContext;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Exception\Exception;

/**
 * Class DbalTransactionInterceptor
 * @package Ecotone\Amqp\DbalTransaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DeduplicationInterceptor
{
    private bool $isInitialized = false;
    private Clock $clock;
    private int $minimumTimeToRemoveMessageInMilliseconds;
    private string $connectionReferenceName;

    public function __construct(string $connectionReferenceName, Clock $clock, int $minimumTimeToRemoveMessageInMilliseconds)
    {
        $this->clock = $clock;
        $this->minimumTimeToRemoveMessageInMilliseconds = $minimumTimeToRemoveMessageInMilliseconds;
        $this->connectionReferenceName = $connectionReferenceName;
    }

    public function deduplicate(MethodInvocation $methodInvocation, $payload, array $headers, ReferenceSearchService $referenceSearchService)
    {
        $connectionFactory = CachedConnectionFactory::createFor(new DbalReconnectableConnectionFactory($referenceSearchService->get($this->connectionReferenceName)));

        if (!$this->isInitialized) {
            $this->createDataBaseTable($connectionFactory);
            $this->isInitialized = true;
        }
        $this->removeExpiredMessages($connectionFactory);
        $messageId = $headers[MessageHeaders::MESSAGE_ID];
        $consumerEndpointId = $headers[MessageHeaders::CONSUMER_ENDPOINT_ID];

        $select = $this->getConnection($connectionFactory)->createQueryBuilder()
            ->select('message_id')
            ->from($this->getTableName())
            ->andWhere('message_id = :messageId')
            ->andWhere('consumer_endpoint_id = :consumerEndpointId')
            ->setParameter('messageId', $messageId, Types::TEXT)
            ->setParameter('consumerEndpointId', $consumerEndpointId, Types::TEXT)
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if ($select) {
            return null;
        }

        $result = $methodInvocation->proceed();
        $this->insertHandledMessage($connectionFactory, $headers);

        return $result;
    }

    private function removeExpiredMessages(ConnectionFactory $connectionFactory): void
    {
        $this->getConnection($connectionFactory)->createQueryBuilder()
            ->delete($this->getTableName())
            ->andWhere('(:now - handled_at) >= :minimumTimeToRemoveTheMessage')
            ->setParameter('now', $this->clock->unixTimeInMilliseconds(), Types::BIGINT)
            ->setParameter('minimumTimeToRemoveTheMessage', $this->minimumTimeToRemoveMessageInMilliseconds, Types::BIGINT)
            ->execute();
    }

    private function insertHandledMessage(ConnectionFactory $connectionFactory, array $headers) : void
    {
        $rowsAffected = $this->getConnection($connectionFactory)->insert(
            $this->getTableName(),
            [
                'message_id' => $headers[MessageHeaders::MESSAGE_ID],
                'handled_at' => $this->clock->unixTimeInMilliseconds(),
                'consumer_endpoint_id' => $headers[MessageHeaders::CONSUMER_ENDPOINT_ID]
            ],
            [
                'id' => Types::TEXT,
                'handled_at' => Types::BIGINT,
                'consumer_endpoint_id' => Types::TEXT
            ]
        );

        if (1 !== $rowsAffected) {
            throw new Exception('There was a problem inserting deduplication. Dbal did not confirm that the record is inserted.');
        }
    }

    private function getTableName(): string
    {
        return "ecotone_outbox";
    }

    private function createDataBaseTable(ConnectionFactory $connectionFactory): void
    {
        $sm = $this->getConnection($connectionFactory)->getSchemaManager();

        if ($sm->tablesExist([$this->getTableName()])) {
            return;
        }

        $table = new Table($this->getTableName());

        $table->addColumn('message_id', Types::STRING);
        $table->addColumn('handled_at', Types::BIGINT);
        $table->addColumn('consumer_endpoint_id', Types::STRING);

        $table->setPrimaryKey(['message_id', 'consumer_endpoint_id']);
        $table->addIndex(['handled_at']);

        $sm->createTable($table);
    }

    private function getConnection(ConnectionFactory $connectionFactory): Connection
    {
        /** @var DbalContext $context */
        $context = $connectionFactory->createContext();

        return $context->getDbalConnection();
    }
}