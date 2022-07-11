<?php

namespace Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Interop\Queue\ConnectionFactory;
use InvalidArgumentException;

class OrderService
{
    private array $orders = [];

    private int $callCounter = 0;

    #[Asynchronous('orders')]
    #[CommandHandler('order.register', 'orderRegister')]
    public function register(string $order, OrderRegisteringGateway $orderRegisteringGateway): void
    {
        $orderRegisteringGateway->place($order);

        if ($this->callCounter === 1) {
            $this->callCounter++;
            throw new InvalidArgumentException('test');
        }

        $this->callCounter++;
    }

    #[CommandHandler('order.register_with_table_creation', 'orderRegister2')]
    public function registerWithTableCreation(string $order, OrderRegisteringGateway $orderRegisteringGateway, #[Reference(DbalConnectionFactory::class)] ConnectionFactory $connection): void
    {
        $schemaManager = $connection->createContext()->getDbalConnection()->createSchemaManager();

        if ($schemaManager->tablesExist(['test_table'])) {
            $schemaManager->dropTable('test_table');
        }

        $table = new Table('test_table');

        $table->addColumn('id', Types::STRING);

        $table->setPrimaryKey(['id']);

        $schemaManager->createTable($table);

        $orderRegisteringGateway->place($order);
    }

    #[Asynchronous('processOrders')]
    #[CommandHandler('placeOrder', 'placeOrderEndpoint')]
    public function placeOrder(string $order): void
    {
        $this->orders[] = $order;
    }

    #[QueryHandler('order.getRegistered')]
    public function getRegistered(): array
    {
        return $this->orders;
    }

    #[CommandHandler('order.prepareWithFailure')]
    public function prepareWithFailure(#[Reference(DbalConnectionFactory::class)] ManagerRegistryConnectionFactory $connectionFactory)
    {
        $connection = $connectionFactory->createContext()->getDbalConnection();

        $connection->executeStatement(<<<SQL
                DROP TABLE IF EXISTS orders
            SQL);
        $connection->executeStatement(<<<SQL
                CREATE TABLE orders (id VARCHAR(255) PRIMARY KEY)
            SQL);

        $connection->executeStatement(<<<SQL
                CREATE TABLE WITH FAILURE SYNTAX orders2 (id VARCHAR(255) PRIMARY KEY)
            SQL);
    }
}
