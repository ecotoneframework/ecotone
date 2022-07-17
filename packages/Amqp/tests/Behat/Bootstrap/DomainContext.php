<?php

namespace Test\Ecotone\Amqp\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Ecotone\Amqp\Distribution\AmqpDistributionModule;
use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Enqueue\AmqpExt\AmqpConnectionFactory;
use Interop\Amqp\Impl\AmqpQueue;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Publisher\UserService;
use Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver\TicketServiceMessagingConfiguration;
use Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver\TicketServiceReceiver;
use Test\Ecotone\Amqp\Fixture\ErrorChannel\ErrorConfigurationContext;
use Test\Ecotone\Amqp\Fixture\FailureTransactionWithFatalError\ChannelConfiguration;
use Test\Ecotone\Amqp\Fixture\Order\OrderErrorHandler;
use Test\Ecotone\Amqp\Fixture\Order\OrderService;
use Test\Ecotone\Amqp\Fixture\Shop\MessagingConfiguration;
use Test\Ecotone\Amqp\Fixture\Shop\ShoppingCart;

/**
 * Defines application features from the specific context.
 *
 * @internal
 */
class DomainContext extends TestCase implements Context
{
    private static ConfiguredMessagingSystem $messagingSystem;
    /**
     * @var ConfiguredMessagingSystem[] $messagingSystems
     */
    private static array $messagingSystems = [];

    /**
     * @Given I active messaging for namespace :namespace
     */
    public function iActiveMessagingForNamespace(string $namespace)
    {
        $host = getenv('RABBIT_HOST') ? getenv('RABBIT_HOST') : 'localhost';

        switch ($namespace) {
            case "Test\Ecotone\Amqp\Fixture\Order":
                {
                    $objects = [
                        new OrderService(),
                        new OrderErrorHandler(),
                    ];
                    break;
                }
            case "Test\Ecotone\Amqp\Fixture\FailureTransaction":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\FailureTransaction\OrderService(),
                    ];
                }
                break;
            case "Test\Ecotone\Amqp\Fixture\SuccessTransaction":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\SuccessTransaction\OrderService(),
                    ];
                }
                break;
            case "Test\Ecotone\Amqp\Fixture\Shop":
                {
                    $objects = [
                        new ShoppingCart(),
                    ];
                }
                break;
            case "Test\Ecotone\Amqp\Fixture\ErrorChannel":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\ErrorChannel\OrderService(),
                    ];
                }
                break;
            case "Test\Ecotone\Amqp\Fixture\DeadLetter":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\DeadLetter\OrderService(),
                    ];
                    break;
                }
            case "Test\Ecotone\Amqp\Fixture\FailureTransactionWithFatalError":
                {
                    $objects = [
                        new \Test\Ecotone\Amqp\Fixture\FailureTransactionWithFatalError\OrderService(),
                    ];
                    break;
                }
        }

        $amqpConnectionFactory = new AmqpConnectionFactory(['dsn' => "amqp://{$host}:5672"]);
        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withNamespaces([$namespace])
            ->withCacheDirectoryPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . Uuid::uuid4()->toString())
            ->withSkippedModulePackageNames(['jmsConverter', 'dbal', 'eventSourcing']);
        MessagingSystemConfiguration::cleanCache($serviceConfiguration->getCacheDirectoryPath());

        self::$messagingSystem = EcotoneLiteConfiguration::createWithConfiguration(
            __DIR__ . '/../../../',
            InMemoryPSRContainer::createFromObjects(array_merge($objects, [$amqpConnectionFactory])),
            $serviceConfiguration,
            [],
            true
        );

        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(ChannelConfiguration::QUEUE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\FailureTransaction\ChannelConfiguration::QUEUE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\SuccessTransaction\ChannelConfiguration::QUEUE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(MessagingConfiguration::SHOPPING_QUEUE));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\Order\ChannelConfiguration::QUEUE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(ErrorConfigurationContext::INPUT_CHANNEL));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\DeadLetter\ErrorConfigurationContext::INPUT_CHANNEL));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(\Test\Ecotone\Amqp\Fixture\DeadLetter\ErrorConfigurationContext::DEAD_LETTER_CHANNEL));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(TicketServiceMessagingConfiguration::SERVICE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue(AmqpDistributionModule::CHANNEL_PREFIX . \Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Receiver\TicketServiceMessagingConfiguration::SERVICE_NAME));
        $amqpConnectionFactory->createContext()->deleteQueue(new AmqpQueue('ecotone_1_delay'));
    }

    /**
     * @Given I active messaging distributed services:
     */
    public function iActiveMessagingDistributedServices(TableNode $table)
    {
        $services = $table->getHash();

        foreach ($services as $service) {
            $namespace = $service['namespace'];
            $serviceName                          = $service['name'];
            $host = getenv('RABBIT_HOST') ? getenv('RABBIT_HOST') : 'localhost';

            switch ($namespace) {
                case "Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Publisher":
                    {
                        $objects = [
                            new UserService(),
                        ];
                        break;
                    }
                case "Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver":
                    {
                        $objects = [
                            new TicketServiceReceiver(),
                        ];
                        break;
                    }
                case "Test\Ecotone\Amqp\Fixture\DistributedEventBus\Publisher":
                    {
                        $objects = [
                            new \Test\Ecotone\Amqp\Fixture\DistributedEventBus\Publisher\UserService(),
                        ];
                        break;
                    }
                case "Test\Ecotone\Amqp\Fixture\DistributedEventBus\Receiver":
                    {
                        $objects = [
                            new \Test\Ecotone\Amqp\Fixture\DistributedEventBus\Receiver\TicketServiceReceiver(),
                        ];
                        break;
                    }
                case "Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Publisher":
                    {
                        $objects = [
                            new \Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Publisher\UserService(),
                        ];
                        break;
                    }
                case "Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Receiver":
                    {
                        $objects = [
                            new \Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Receiver\TicketServiceReceiver(),
                        ];
                        break;
                    }
            }

            $amqpConnectionFactory         = new AmqpConnectionFactory(['dsn' => "amqp://{$host}:5672"]);
            self::$messagingSystems[$serviceName] = EcotoneLiteConfiguration::createWithConfiguration(
                __DIR__ . '/../../../',
                InMemoryPSRContainer::createFromObjects(array_merge($objects, [$amqpConnectionFactory])),
                ServiceConfiguration::createWithDefaults()
                    ->withNamespaces([$namespace])
                    ->withServiceName($serviceName)
                    ->withSkippedModulePackageNames(['jmsConverter', 'dbal', 'eventSourcing'])
                    ->withCacheDirectoryPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . Uuid::uuid4()->toString()),
                [],
                false
            );
        }
    }

    /**
     * @When I order :order
     */
    public function iOrder(string $order)
    {
        return $this->getCommandBus()->sendWithRouting('order.register', $order);
    }

    private function getCommandBus(): CommandBus
    {
        return self::$messagingSystem->getGatewayByName(CommandBus::class);
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
     * @Then on the order list I should see :order
     */
    public function onTheOrderListIShouldSee(string $order)
    {
        $this->assertEquals(
            [$order],
            $this->getQueryBus()->sendWithRouting('order.getOrders', [])
        );
    }

    private function getQueryBus(): QueryBus
    {
        return self::$messagingSystem->getGatewayByName(QueryBus::class);
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
     * @When I add product :productName to shopping cart
     */
    public function iAddProductToShoppingCart(string $productName)
    {
        /** @var CommandBus $commandBus */
        $commandBus = self::$messagingSystem->getGatewayByName(CommandBus::class);

        $commandBus->sendWithRouting('addToBasket', $productName);
    }

    /**
     * @Then there should be product :productName in shopping cart
     */
    public function thereShouldBeProductInShoppingCart(string $productName)
    {
        /** @var QueryBus $queryBus */
        $queryBus = self::$messagingSystem->getGatewayByName(QueryBus::class);

        $this->assertEquals(
            [$productName],
            $queryBus->sendWithRouting('getShoppingCartList', [])
        );
    }

    /**
     * @Then there should be :orderName order
     */
    public function thereShouldBeOrder(string $orderName)
    {
        $this->assertEquals(
            $orderName,
            $this->getQueryBus()->sendWithRouting('order.getOrder', [])
        );
    }

    /**
     * @Then there should be no :orderName order
     */
    public function thereShouldBeNoOrder(string $orderName)
    {
        $this->assertNull(
            $this->getQueryBus()->sendWithRouting('order.getOrder', [])
        );
    }

    /**
     * @Then there should be :amount orders
     */
    public function thereShouldBeOrders(int $amount)
    {
        $this->assertEquals(
            $amount,
            $this->getQueryBus()->sendWithRouting('getOrderAmount', [])
        );
    }

    /**
     * @Then there should be :amount incorrect orders
     */
    public function thereShouldBeIncorrectOrders(int $amount)
    {
        $this->assertEquals(
            $amount,
            $this->getQueryBus()->sendWithRouting('getIncorrectOrderAmount', [])
        );
    }

    /**
     * @When I call consumer :consumerName
     */
    public function iCallConsumer(string $consumerName)
    {
        self::$messagingSystem->run($consumerName);
    }

    /**
     * @When using :serviceName I change billing details
     */
    public function usingIChangeBillingDetails(string $serviceName)
    {
        /** @var CommandBus $commandBus */
        $commandBus = self::$messagingSystems[$serviceName]->getGatewayByName(CommandBus::class);

        $commandBus->sendWithRouting(UserService::CHANGE_BILLING_DETAILS, $serviceName);
    }

    /**
     * @Then using :serviceName I should have :amount remaining ticket
     */
    public function usingIShouldHaveRemainingTicket(string $serviceName, int $amount)
    {
        self::$messagingSystems[$serviceName]->run($serviceName);
        /** @var QueryBus $queryBus */
        $queryBus = self::$messagingSystems[$serviceName]->getGatewayByName(QueryBus::class);

        $this->assertEquals($amount, $queryBus->sendWithRouting(TicketServiceReceiver::GET_TICKETS_COUNT, []));
    }

    /**
     * @When using :serviceName there are :amount error tickets
     */
    public function usingThereAreErrorTickets(string $serviceName, int $amount)
    {
        /** @var QueryBus $queryBus */
        $queryBus = self::$messagingSystems[$serviceName]->getGatewayByName(QueryBus::class);

        $this->assertEquals($amount, $queryBus->sendWithRouting(\Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Receiver\TicketServiceReceiver::GET_ERROR_TICKETS_COUNT, []));
    }

    /**
     * @When using :serviceName process ticket with failure
     */
    public function usingProcessTicketWithFailure(string $serviceName)
    {
        self::$messagingSystems[$serviceName]->run($serviceName);
    }
}
