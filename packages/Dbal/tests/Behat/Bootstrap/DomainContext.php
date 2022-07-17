<?php

namespace Test\Ecotone\Dbal\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Ecotone\Dbal\DbalConnection;
use Ecotone\Dbal\DocumentStore\DbalDocumentStore;
use Ecotone\Dbal\Recoverability\DbalDeadLetter;
use Ecotone\Dbal\Recoverability\DeadLetterGateway;
use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Enqueue\Dbal\DbalConnectionFactory;
use InvalidArgumentException;

use function json_decode;
use function json_encode;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Dbal\Fixture\DeadLetter\OrderGateway;
use Test\Ecotone\Dbal\Fixture\DocumentStoreAggregate\PersonJsonConverter;
use Test\Ecotone\Dbal\Fixture\ORM\RegisterPerson;
use Test\Ecotone\Dbal\Fixture\Transaction\OrderService;

/**
 * Defines application features from the specific context.
 *
 * @internal
 */
class DomainContext extends TestCase implements Context
{
    /**
     * @var ConfiguredMessagingSystem
     */
    private static $messagingSystem;

    /**
     * @Given I active messaging for namespace :namespace
     */
    public function iActiveMessagingForNamespace(string $namespace)
    {
        switch ($namespace) {
            case "Test\Ecotone\Dbal\Fixture\Transaction": {
                $objects = [
                    new OrderService(),
                ];
                break;
            }
            case "Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction": {
                $objects = [
                    new \Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction\OrderService(),
                ];
                break;
            }
            case "Test\Ecotone\Dbal\Fixture\DeadLetter": {
                $objects = [
                    new \Test\Ecotone\Dbal\Fixture\DeadLetter\OrderService(),
                ];
                break;
            }
            case "Test\Ecotone\Dbal\Fixture\ORM": {
                $objects = [];
                break;
            }
            case "Test\Ecotone\Dbal\Fixture\DocumentStore": {
                $objects = [];
                break;
            }
            case "Test\Ecotone\Dbal\Fixture\InMemoryDocumentStore": {
                $objects = [];
                break;
            }
            case "Test\Ecotone\Dbal\Fixture\DocumentStoreAggregate": {
                $objects = [new PersonJsonConverter()];
                break;
            }
            default: {
                throw new InvalidArgumentException("Namespace {$namespace} not yet implemented");
            }
        }

        $dsn = getenv('DATABASE_DSN') ? getenv('DATABASE_DSN') : null;
        $connectionFactory = DbalConnection::fromConnectionFactory(new DbalConnectionFactory(['dsn' => $dsn]));
        $connection = $connectionFactory->createContext()->getDbalConnection();
        $enqueueTable = 'enqueue';
        if ($this->checkIfTableExists($connection, $enqueueTable)) {
            $this->deleteFromTableExists($enqueueTable, $connection);
            $this->deleteFromTableExists(OrderService::ORDER_TABLE, $connection);
            $this->deleteFromTableExists(DbalDeadLetter::DEFAULT_DEAD_LETTER_TABLE, $connection);
            $this->deleteFromTableExists(DbalDocumentStore::ECOTONE_DOCUMENT_STORE, $connection);
        }

        $rootProjectDirectoryPath = __DIR__ . '/../../../';
        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withNamespaces([$namespace])
            ->withCacheDirectoryPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . Uuid::uuid4()->toString())
            ->withSkippedModulePackageNames(['jmsConverter', 'amqp', 'eventSourcing']);
        MessagingSystemConfiguration::cleanCache($serviceConfiguration->getCacheDirectoryPath());

        switch ($namespace) {
            case "Test\Ecotone\Dbal\Fixture\ORM": {
                if (! $this->checkIfTableExists($connection, 'persons')) {
                    $connection->executeStatement(<<<SQL
                            CREATE TABLE persons (
                                person_id INTEGER PRIMARY KEY,
                                name VARCHAR(255)
                            )
                        SQL);
                }
                $this->deleteFromTableExists('persons', $connection);

                $config = Setup::createAnnotationMetadataConfiguration([$rootProjectDirectoryPath . DIRECTORY_SEPARATOR . 'tests/Dbal/Fixture/ORM'], true, null, null, false);

                $objects = [
                    DbalConnectionFactory::class => DbalConnection::createEntityManager(EntityManager::create(['url' => $dsn], $config)),
                ];
                break;
            }
            default: {
                $objects = array_merge($objects, ['managerRegistry' => $connectionFactory, DbalConnectionFactory::class => $connectionFactory]);
            }
        }

        self::$messagingSystem            = EcotoneLiteConfiguration::createWithConfiguration(
            $rootProjectDirectoryPath,
            InMemoryPSRContainer::createFromObjects($objects),
            $serviceConfiguration,
            [],
            true
        );
    }

    private function deleteFromTableExists(string $tableName, \Doctrine\DBAL\Connection $connection): void
    {
        $doesExists = $this->checkIfTableExists($connection, $tableName);

        if ($doesExists) {
            $connection->executeStatement('DELETE FROM ' . $tableName);
        }
    }

    /**
     * @When I active receiver :receiverName
     * @param string $receiverName
     */
    public function iActiveReceiver(string $receiverName)
    {
        self::$messagingSystem->run($receiverName);
    }

    /**
     * @Then there should be nothing on the order list
     */
    public function thereShouldBeNothingOnTheOrderList()
    {
        $this->assertEquals(
            [],
            $this->getQueryBus()->sendWithRouting('order.getOrders', [])
        );
    }

    private function getCommandBus(): CommandBus
    {
        return self::$messagingSystem->getGatewayByName(CommandBus::class);
    }

    private function getQueryBus(): QueryBus
    {
        return self::$messagingSystem->getGatewayByName(QueryBus::class);
    }

    private function getDocumentStore(): DocumentStore
    {
        return self::$messagingSystem->getGatewayByName(DocumentStore::class);
    }

    /**
     * @When I transactionally order :order
     */
    public function iTransactionallyOrder(string $order)
    {
        /** @var CommandBus $commandBus */
        $commandBus = self::$messagingSystem->getGatewayByName(CommandBus::class);

        try {
            $commandBus->sendWithRouting('order.register', $order);
        } catch (InvalidArgumentException $e) {
        }
    }

    /**
     * @When I order :order
     */
    public function iOrder(string $order)
    {
        /** @var OrderGateway $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(OrderGateway::class);

        $gateway->order($order);
    }

    /**
     * @When I call pollable endpoint :consumerId
     */
    public function iCallPollableEndpoint(string $consumerId)
    {
        self::$messagingSystem->run($consumerId);
    }

    /**
     * @Then there should be :amount orders
     */
    public function thereShouldBeOrders(int $amount)
    {
        /** @var OrderGateway $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(OrderGateway::class);

        $this->assertEquals(
            $amount,
            $gateway->getOrderAmount()
        );
    }

    /**
     * @Then there should :amount error message in dead letter
     */
    public function thereShouldErrorMessageInDeadLetter(int $amount)
    {
        /** @var DeadLetterGateway $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(DeadLetterGateway::class);

        $this->assertEquals(
            $amount,
            count($gateway->list(100, 0))
        );
    }

    /**
     * @When all error messages are replied
     */
    public function whenAllErrorMessagesAreReplied()
    {
        /** @var DeadLetterGateway $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(DeadLetterGateway::class);

        $gateway->replyAll();
    }

    /**
     * @Then there should :amount registered orders
     */
    public function thereShouldRegisteredOrders($amount)
    {
        $this->assertEquals(
            $amount,
            count($this->getQueryBus()->sendWithRouting('order.getRegistered', []))
        );
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     * @param string $table
     * @return false|mixed
     * @throws \Doctrine\DBAL\Exception
     */
    private function checkIfTableExists(\Doctrine\DBAL\Connection $connection, string $table): mixed
    {
        $schemaManager = $connection->createSchemaManager();

        return $schemaManager->tablesExist([$table]);
    }

    /**
     * @When I register person with id :personId and name :name
     */
    public function iRegisterPersonWithIdAndName(int $personId, string $name)
    {
        $this->getCommandBus()->send(new RegisterPerson($personId, $name));
    }

    /**
     * @Then there person with id :personId should be named :name
     */
    public function therePersonWithIdShouldBeNamed(int $personId, string $name)
    {
        $this->assertEquals(
            $name,
            $this->getQueryBus()->sendWithRouting('person.getName', ['personId' => $personId])
        );
    }

    /**
     * @When I place order nr :orderId for :order in :shopName
     */
    public function iPlaceOrderNrForIn(int $orderId, string $order, string $shopName)
    {
        $this->getDocumentStore()->addDocument(
            $shopName,
            $orderId,
            $this->convertOrderToJson($order)
        );
    }

    /**
     * @Then there should be order nr :orderId3 in :shopName with :order
     */
    public function thereShouldBeOrderNrInWith(int $orderId, string $shopName, string $order)
    {
        Assert::assertEquals(
            $this->convertOrderToJson($order),
            json_encode(json_decode($this->getDocumentStore()->getDocument($shopName, $orderId)))
        );
        Assert::assertEquals(
            $this->convertOrderToJson($order),
            json_encode(json_decode($this->getDocumentStore()->findDocument($shopName, $orderId), true))
        );
    }

    /**
     * @Then there should :numberOfOrders order placed in :shopName
     */
    public function thereShouldOrderPlacedIn(int $numberOfOrders, string $shopName)
    {
        Assert::assertEquals($numberOfOrders, $this->getDocumentStore()->countDocuments($shopName));
        Assert::assertEquals($numberOfOrders, count($this->getDocumentStore()->getAllDocuments($shopName)));
    }

    /**
     * @When I update order nr :orderId in :shopName to :order
     */
    public function iUpdateOrderNrInTo(int $orderId, string $shopName, string $order)
    {
        $this->getDocumentStore()->updateDocument($shopName, $orderId, $this->convertOrderToJson($order));
    }

    /**
     * @When I upsert order nr :orderId for :order in :shopName
     */
    public function iUpsertOrderNrForIn(int $orderId, string $order, string $shopName)
    {
        $this->getDocumentStore()->upsertDocument($shopName, $orderId, $this->convertOrderToJson($order));
    }

    /**
     * @When I delete order nr :orderId in :shopName
     */
    public function iDeleteOrderNrIn(int $orderId, string $shopName)
    {
        $this->getDocumentStore()->deleteDocument($shopName, $orderId);
    }

    private function convertOrderToJson(string $order): string
    {
        return json_encode(['data' => $order]);
    }

    /**
     * @When I register person with id :id and name :name for document aggregate
     */
    public function iRegisterPersonWithIdAndNameForDocumentAggregate(int $id, string $name)
    {
        $this->getCommandBus()->send(new \Test\Ecotone\Dbal\Fixture\DocumentStoreAggregate\RegisterPerson($id, $name));
    }

    /**
     * @Then there person with id :id should be named :name  for document aggregate
     */
    public function therePersonWithIdShouldBeNamedForDocumentAggregate(int $id, string $name)
    {
        Assert::assertEquals(
            $name,
            $this->getQueryBus()->sendWithRouting('person.getName', metadata: ['aggregate.id' => $id])
        );
    }

    /**
     * @When I transactionally order :order with table creation
     */
    public function iTransactionallyOrderWithTableCreation(string $order)
    {
        /** @var CommandBus $commandBus */
        $commandBus = self::$messagingSystem->getGatewayByName(CommandBus::class);

        try {
            $commandBus->sendWithRouting('order.register_with_table_creation', $order);
        } catch (InvalidArgumentException $e) {
        }
    }

    /**
     * @Given table is prepared
     */
    public function tableIsPrepared()
    {
        /** @var CommandBus $commandBus */
        $commandBus = self::$messagingSystem->getGatewayByName(CommandBus::class);

        $commandBus->sendWithRouting('order.prepare');
    }

    /**
     * @When it fails prepare table
     */
    public function itFailsPrepareTable()
    {
        /** @var CommandBus $commandBus */
        $commandBus = self::$messagingSystem->getGatewayByName(CommandBus::class);

        try {
            $commandBus->sendWithRouting('order.prepareWithFailure');
        } catch (\Exception) {
        }
    }
}
