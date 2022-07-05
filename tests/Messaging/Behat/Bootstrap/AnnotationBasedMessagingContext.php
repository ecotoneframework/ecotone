<?php


namespace Tests\Ecotone\Messaging\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionException;
use Tests\Ecotone\Messaging\Fixture\Behat\Calculating\Calculator;
use Tests\Ecotone\Messaging\Fixture\Behat\Calculating\CalculatorInterceptor;
use Tests\Ecotone\Messaging\Fixture\Behat\Calculating\InboundCalculation;
use Tests\Ecotone\Messaging\Fixture\Behat\Calculating\ResultService;
use Tests\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter\ErrorReceiver;
use Tests\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter\OrderGateway;
use Tests\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter\OrderService;
use Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGateway\CalculateGatewayExample;
use Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGateway\InterceptorExample;
use Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGateway\SomeQueryHandler;
use Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\CalculateGatewayExampleWithMessages;
use Tests\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled\InterceptedScheduledExample;
use Tests\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled\InterceptedScheduledGateway;
use Tests\Ecotone\Messaging\Fixture\Behat\Presend\CoinGateway;
use Tests\Ecotone\Messaging\Fixture\Behat\Presend\MultiplyCoins;
use Tests\Ecotone\Messaging\Fixture\Behat\Presend\Shop;
use Tests\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Tests\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\OrderNotificator;
use Tests\Ecotone\Modelling\Fixture\DistributedCommandHandler\ShoppingCenter;
use Tests\Ecotone\Modelling\Fixture\DistributedEventHandler\ShoppingRecord;
use Tests\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\JobRepository;
use Tests\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\AddExecutorId\AddExecutorId;
use Tests\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\AddNotificationTimestamp\AddNotificationTimestamp;
use Tests\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\LoggerRepository;
use Tests\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\NotificationService;
use Tests\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\VerifyAccessToSavingLogs\HasEnoughPermissions;
use Tests\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddFranchiseMargin\AddFranchiseMargin;
use Tests\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddVat\AddVatService;
use Tests\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\ProductToPriceExchange\ProductExchanger;
use Tests\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\ShopRepository;
use Tests\Ecotone\Modelling\Fixture\InterceptingAggregate\AddCurrentUserId;
use Tests\Ecotone\Modelling\Fixture\InterceptingAggregate\BasketRepository;
use Tests\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes\AddMetadataService;
use Tests\Ecotone\Modelling\Fixture\LateAggregateIdAssignation\UserRepository;
use Tests\Ecotone\Modelling\Fixture\MultipleHandlersAtSameMethod\Basket;
use Tests\Ecotone\Modelling\Fixture\NamedEvent\GuestBookRepository;
use Tests\Ecotone\Modelling\Fixture\NamedEvent\GuestViewer;
use Tests\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Tests\Ecotone\Modelling\Fixture\OrderAggregate\AddUserId\AddUserIdService;
use Tests\Ecotone\Modelling\Fixture\OrderAggregate\LoggingService;
use Tests\Ecotone\Modelling\Fixture\OrderAggregate\OrderErrorHandler;
use Tests\Ecotone\Modelling\Fixture\OrderAggregate\OrderRepository;
use Tests\Ecotone\Modelling\Fixture\Renter\AppointmentStandardRepository;
use Tests\Ecotone\Modelling\Fixture\Renter\CreateAppointmentCommand;
use Tests\Ecotone\Modelling\Fixture\Renter\RentCalendar;
use Tests\Ecotone\Modelling\Fixture\RepositoryShortcut\Infrastructure\TwitterRepository;
use Tests\Ecotone\Modelling\Fixture\SimplifiedAggregate\IdGenerator;
use Tests\Ecotone\Modelling\Fixture\SimplifiedAggregate\SimplifiedAggregateRepository;
use Tests\Ecotone\Modelling\Fixture\TwoSagas\TwoSagasRepository;

class AnnotationBasedMessagingContext extends TestCase implements Context
{
    private static ?\Ecotone\Messaging\Config\ConfiguredMessagingSystem $messagingSystem;

    private static array $loadedNamespaces = [];

    /**
     * @Given I active messaging for namespace :namespace
     * @param string $namespace
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function iActiveMessagingForNamespace(string $namespace)
    {
        self::$loadedNamespaces = [$namespace];

        switch ($namespace) {
            case "Tests\Ecotone\Modelling\Fixture\Renter":
                {
                    $objects = [
                        RentCalendar::class => new RentCalendar(),
                        AppointmentStandardRepository::class => AppointmentStandardRepository::createEmpty()
                    ];
                    break;
                }
            case "Tests\Ecotone\Modelling\Fixture\CommandHandler\Aggregate":
                {
                    $objects = [
                        OrderNotificator::class => new OrderNotificator(),
                        InMemoryStandardRepository::class => InMemoryStandardRepository::createEmpty()
                    ];
                    break;
                }
            case "Tests\Ecotone\Messaging\Fixture\Behat\ErrorHandling":
                {
                    $objects = [
                        ErrorReceiver::class => new ErrorReceiver(),
                        OrderService::class => new OrderService()
                    ];
                    break;
                }
            case "Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGateway":
                {
                    $objects = [
                        InterceptorExample::class => new InterceptorExample(),
                        SomeQueryHandler::class => new SomeQueryHandler()
                    ];
                    break;
                }
            case "Tests\Ecotone\Messaging\Fixture\Behat\InterceptedGateway":
                {
                    $objects = [
                        \Tests\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\InterceptorExample::class => new \Tests\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\InterceptorExample(),
                        \Tests\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\SomeQueryHandler::class => new \Tests\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\SomeQueryHandler()
                    ];
                    break;
                }
            case "Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages":
                {
                    $objects = [
                        \Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\InterceptorExample::class => new \Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\InterceptorExample(),
                        \Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\SomeQueryHandler::class => new \Tests\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\SomeQueryHandler()
                    ];
                    break;
                }
            case "Tests\Ecotone\Modelling\Fixture\Order":
                {
                    $objects = [
                        \Tests\Ecotone\Modelling\Fixture\Order\OrderService::class => new \Tests\Ecotone\Modelling\Fixture\Order\OrderService()
                    ];
                    break;
                }
            case "Tests\Ecotone\Modelling\Fixture\OrderAggregate":
                {
                    $objects = [
                        OrderRepository::class => OrderRepository::createEmpty(),
                        AddUserIdService::class => new AddUserIdService(),
                        OrderErrorHandler::class => new OrderErrorHandler(),
                        LoggingService::class => new LoggingService()
                    ];
                    break;
                }
            case "Tests\Ecotone\Modelling\Fixture\NamedEvent":
                {
                    $objects = [
                        new GuestViewer(),
                        GuestBookRepository::createEmpty()
                    ];
                    break;
                }
            case "Tests\Ecotone\Messaging\Fixture\Behat\Presend":
                {
                    $objects = [
                        MultiplyCoins::class => new MultiplyCoins(),
                        Shop::class => new Shop(),
                        LoggingService::class => new LoggingService()
                    ];
                    break;
                }
            case "Tests\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled":
                {
                    $objects = [
                        InterceptedScheduledExample::class => new InterceptedScheduledExample(),
                        LoggingService::class => new LoggingService()
                    ];
                    break;
                }
            case "Tests\Ecotone\Modelling\Fixture\InterceptedQueryAggregate":
                {
                    $objects = [
                        ProductExchanger::class => new ProductExchanger(),
                        AddVatService::class => new AddVatService(),
                        AddFranchiseMargin::class => new AddFranchiseMargin(),
                        ShopRepository::class => ShopRepository::createEmpty()
                    ];
                    break;
                }
            case "Tests\Ecotone\Modelling\Fixture\InterceptedCommandAggregate":
                {
                    $objects = [
                        LoggerRepository::class => new LoggerRepository(),
                        NotificationService::class => new NotificationService(),
                        HasEnoughPermissions::class => new HasEnoughPermissions(),
                        AddNotificationTimestamp::class => new AddNotificationTimestamp(),
                        AddExecutorId::class => new AddExecutorId()
                    ];
                    break;
                }
            case "Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate":
                {
                    $objects = [
                        \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\LoggerRepository::class => new \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\LoggerRepository(),
                        \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\NotificationService::class => new \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\NotificationService(),
                        \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\VerifyAccessToSavingLogs\HasEnoughPermissions::class => new \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\VerifyAccessToSavingLogs\HasEnoughPermissions(),
                        \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddNotificationTimestamp\AddNotificationTimestamp::class => new \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddNotificationTimestamp\AddNotificationTimestamp(),
                        \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddExecutorId\AddExecutorId::class => new \Tests\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddExecutorId\AddExecutorId()
                    ];
                    break;
                }
            case "Tests\Ecotone\Modelling\Fixture\MetadataPropagating":
                {
                    $objects = [
                        new \Tests\Ecotone\Modelling\Fixture\MetadataPropagating\OrderService()
                    ];
                    break;
                }
            case "Tests\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints":
            {
                $objects = [
                    new \Tests\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints\OrderService()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\InterceptingAggregate":
            {
                $objects = [
                    new AddCurrentUserId(),
                    BasketRepository::createEmpty()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes":
            {
                $objects = [
                    new AddMetadataService(),
                    \Tests\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes\BasketRepository::createEmpty()
                ];
                break;
            }
            case "Tests\Ecotone\Messaging\Fixture\Behat\Calculating":
            {
                $objects = [
                    InboundCalculation::class => new InboundCalculation(),
                    ResultService::class => new ResultService(),
                    CalculatorInterceptor::class => new CalculatorInterceptor()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\DistributedCommandHandler":
            {
                $objects = [
                    new ShoppingCenter()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\DistributedEventHandler":
            {
                $objects = [
                    new ShoppingRecord()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\MultipleHandlersAtSameMethod":
            {
                $objects = [
                    new Basket()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder":
            {
                $objects = [
                    new JobRepository()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\SimplifiedAggregate":
            {
                $objects = [
                    new IdGenerator(),
                    SimplifiedAggregateRepository::class => SimplifiedAggregateRepository::createEmpty()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\TwoSagas":
            {
                $objects = [
                    TwoSagasRepository::createEmpty()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\TwoAsynchronousSagas":
            {
                $objects = [
                    \Tests\Ecotone\Modelling\Fixture\TwoAsynchronousSagas\TwoSagasRepository::createEmpty()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\RepositoryShortcut":
            {
                $objects = [
                    new TwitterRepository()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut":
            {
                $objects = [
                    new \Tests\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut\Infrastructure\TwitterRepository()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\LateAggregateIdAssignation":
            {
                $objects = [
                    new UserRepository()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\LateAggregateIdAssignationWithAggregateIdFromMethod":
            {
                $objects = [
                    new \Tests\Ecotone\Modelling\Fixture\LateAggregateIdAssignationWithAggregateIdFromMethod\UserRepository()
                ];
                break;
            }
            case "Tests\Ecotone\Modelling\Fixture\AggregateIdFromMethod":
            {
                $objects = [
                    new \Tests\Ecotone\Modelling\Fixture\AggregateIdFromMethod\UserRepository()
                ];
                break;
            }
            default:
            {
                throw new \InvalidArgumentException("Namespace not registered ". $namespace);
            }
        }

        $objects["logger"] = new NullLogger();
        $cacheDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "ecotone_testing_behat_cache";

        $applicationConfiguration = ServiceConfiguration::createWithDefaults()
            ->withEnvironment("prod")
            ->withCacheDirectoryPath($cacheDirectoryPath)
            ->withFailFast(false)
            ->withNamespaces([$namespace]);

        MessagingSystemConfiguration::cleanCache($applicationConfiguration->getCacheDirectoryPath());
        self::$messagingSystem = EcotoneLiteConfiguration::createWithConfiguration(
            __DIR__ . "/../../../../",
            InMemoryPSRContainer::createFromObjects($objects),
            $applicationConfiguration,
            [],
            true
        );
    }

    public static function getLoadedNamespaces(): array
    {
        return self::$loadedNamespaces;
    }

    /**
     * @When I calculate for :amount using gateway
     * @param int $amount
     */
    public function iCalculateForUsingGateway(int $amount)
    {
        /** @var Calculator $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(Calculator::class);

        $gateway->calculate($amount);
    }

    /**
     * @Then the result should be :amount
     * @param int $amount
     * @throws ConfigurationException
     */
    public function theResultShouldBe(int $amount)
    {
        /** @var PollableChannel $resultChannel */
        $resultChannel = self::$messagingSystem->getMessageChannelByName("resultChannel");

        $message = $resultChannel->receive();
        Assert::assertNotNull($message, "Result was never received");
        Assert::assertEquals($amount, $message->getPayload());
    }

    /**
     * @When I calculate for :amount using gateway then result should be :result
     * @param int $amount
     * @param int $result
     */
    public function iCalculateForUsingGatewayThenResultShouldBe(int $amount, int $result)
    {
        /** @var Calculator $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(Calculator::class);

        Assert::assertEquals($result, $gateway->calculate($amount));
    }

    /**
     * @When I calculate using inbound channel adapter
     */
    public function iCalculateUsingInboundChannelAdapter()
    {
        self::$messagingSystem->run("inboundCalculator");
    }

    /**
     * @Then result should be :result in :channelName channel
     * @param $result
     * @param string $channelName
     * @throws ConfigurationException
     */
    public function resultShouldBeInChannel($result, string $channelName)
    {
        /** @var PollableChannel $messageChannel */
        $messageChannel = self::$messagingSystem->getMessageChannelByName($channelName);

        Assert::assertEquals($result, $messageChannel->receive()->getPayload());
    }

    /**
     * @When I rent appointment with id :appointmentId and duration :duration
     * @param int $appointmentId
     * @param int $duration
     */
    public function iRentAppointmentWithIdAndDuration(int $appointmentId, int $duration)
    {
        self::getCommandBus()->send(new CreateAppointmentCommand($appointmentId, $duration));
    }

    /**
     * @return CommandBus
     */
    public static function getCommandBus(): CommandBus
    {
        return self::$messagingSystem->getGatewayByName(CommandBus::class);
    }

    public static function getEventBus(): EventBus
    {
        return self::$messagingSystem->getGatewayByName(EventBus::class);
    }

    /**
     * @Then calendar should contain event with appointment id :appointmentId
     * @param int $appointmentId
     */
    public function calendarShouldContainEventWithAppointmentId(int $appointmentId)
    {
        Assert::assertTrue(self::getQueryBus()->sendWithRouting("doesCalendarContainAppointments", $appointmentId));
    }

    /**
     * @return QueryBus
     */
    public static function getQueryBus(): QueryBus
    {
        return self::$messagingSystem->getGatewayByName(QueryBus::class);
    }

    /**
     * @When I order :order
     */
    public function iOrder(string $order)
    {
        /** @var OrderGateway $orderGateway */
        $orderGateway = self::$messagingSystem->getGatewayByName(OrderGateway::class);

        $orderGateway->order($order);
    }

    /**
     * @When I call pollable endpoint :endpointId
     */
    public function iCallPollableEndpoint(string $endpointId)
    {
        self::$messagingSystem->run($endpointId);
    }

    /**
     * @Then there should no error order
     */
    public function thereShouldNoErrorOrder()
    {
        /** @var OrderGateway $orderGateway */
        $orderGateway = self::$messagingSystem->getGatewayByName(OrderGateway::class);

        $errorOrder = $orderGateway->getIncorrectOrder();
        Assert::assertNull($errorOrder, "There should no error order, but found one {$errorOrder}");
    }

    /**
     * @Then there should be error order :order
     */
    public function thereShouldBeErrorOrder(string $order)
    {
        /** @var OrderGateway $orderGateway */
        $orderGateway = self::$messagingSystem->getGatewayByName(OrderGateway::class);

        $errorOrder = $orderGateway->getIncorrectOrder();
        Assert::assertNotNull($errorOrder, "Expected error order {$order}, but found none");
        Assert::assertEquals($order, $errorOrder, "Expected error order {$order} got {$errorOrder}");
    }

    public static function getGateway(string $name)
    {
        return self::$messagingSystem->getGatewayByName($name);
    }

    /**
     * @When I call with :beginningValue I should receive :result with message
     */
    public function iCallWithIShouldReceiveWithMessage(int $beginningValue, int $result)
    {
        $requestMessage = MessageBuilder::withPayload($beginningValue)
            ->build();
        /** @var Message $replyMessage */
        $replyMessage = self::getGateway(CalculateGatewayExampleWithMessages::class)->calculate($requestMessage);
        Assert::assertEquals($result, $replyMessage->getPayload());
    }

    /**
     * @Then there should be nothing on the order list
     */
    public function thereShouldBeNothingOnTheOrderList()
    {
        $this->assertEquals(
            [],
            $this->getQueryBus()->sendWithRouting("order.getOrders", [])
        );
    }

    /**
     * @When I active receiver :receiverName
     */
    public function iActiveReceiver($receiverName)
    {
        self::$messagingSystem->run($receiverName);
    }

    /**
     * @Then on the order list I should see :order
     */
    public function onTheOrderListIShouldSee(string $order)
    {
        $this->assertEquals(
            [new PlaceOrder($order)],
            $this->getQueryBus()->sendWithRouting("order.getOrders", [])
        );
    }

    /**
     * @When I order product :order
     */
    public function iOrderProduct(string $order)
    {
        return $this->getCommandBus()->sendWithRouting("order.register", new PlaceOrder($order));
    }


    /**
     * @Then there should be :orderName order
     */
    public function thereShouldBeOrder(string $orderName)
    {
        Assert::assertNotNull($this->getQueryBus()->sendWithRouting("order.getOrder", ["orderId" => $orderName]));
    }

    /**
     * @Then there should be no :orderName order
     */
    public function thereShouldBeNoOrder(string $orderName)
    {
        $aggregateFound = true;
        try {
            $this->getQueryBus()->sendWithRouting("order.getOrder", ["orderId" => $orderName]);
        }catch (AggregateNotFoundException $exception) {
            $aggregateFound = false;
        }

        Assert::assertFalse($aggregateFound, "Aggregate was found but should not");
    }

    /**
     * @Then notification list should be empty
     */
    public function notificationListShouldBeEmpty()
    {
        Assert::assertEmpty($this->getQueryBus()->sendWithRouting("order.getNotifiedOrders", []));
    }

    /**
     * @Then on notification list I should see :orderName
     */
    public function onNotificationListIShouldSee(string $orderName)
    {
        $this->assertEquals(
            [$orderName],
            $this->getQueryBus()->sendWithRouting("order.getNotifiedOrders", [])
        );
    }

    /**
     * @Then no notification for :orderName
     */
    public function noNotificationFor(string $orderName)
    {
        $this->assertEquals(0, $this->getQueryBus()->sendWithRouting("order.wasNotified", ["orderId" => $orderName]));
    }

    /**
     * @Then there should be notification about :orderName :number time
     */
    public function thereShouldBeNotificationAboutTime(string $orderName, int $number)
    {
        $this->assertEquals($number, $this->getQueryBus()->sendWithRouting("order.wasNotified", ["orderId" => $orderName]));
    }

    /**
     * @Then logs count be :count
     */
    public function logsCountBe(int $count)
    {
        $this->assertEquals($count, count($this->getQueryBus()->sendWithRouting("getLogs", [])));
    }

    /**
     * @When I store :amount coins
     */
    public function iStoreCoins(int $amount)
    {
        /** @var CoinGateway $coinGateway */
        $coinGateway = self::$messagingSystem->getGatewayByName(CoinGateway::class);

        $coinGateway->store($amount);
    }

    /**
     * @Then result from scheduled endpoint should be :expectedAmount
     */
    public function resultFromScheduledEndpointShouldBe(int $expectedAmount)
    {
        /** @var InterceptedScheduledGateway $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(InterceptedScheduledGateway::class);

        Assert::assertEquals(
            $expectedAmount,
            $gateway->getInterceptedData()
        );
    }
}
