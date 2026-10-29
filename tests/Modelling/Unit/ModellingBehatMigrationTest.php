<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\MessageHandling\Distribution\DistributionEntrypoint;
use Ecotone\Modelling\QueryBus;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetShippingAddressQuery;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\OrderNotificator;
use Test\Ecotone\Modelling\Fixture\DistributedCommandHandler\ShoppingCenter;
use Test\Ecotone\Modelling\Fixture\DistributedEventHandler\ShoppingRecord;
use Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\FinishJob;
use Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\JobRepository;
use Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\StartJob;
use Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut\TwitWasCreated;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\AddExecutorId\AddExecutorId;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\AddNotificationTimestamp\AddNotificationTimestamp;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\EventWasLogged;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\LoggerRepository;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\NotificationService;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\VerifyAccessToSavingLogs\HasEnoughPermissions;
use Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddFranchiseMargin\AddFranchiseMargin;
use Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddVat\AddVatService;
use Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\ProductToPriceExchange\ProductExchanger;
use Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\ShopRepository;
use Test\Ecotone\Modelling\Fixture\InterceptingAggregate\AddCurrentUserId;
use Test\Ecotone\Modelling\Fixture\InterceptingAggregate\BasketRepository;
use Test\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes\AddMetadataService;
use Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation\CreateUser;
use Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation\UserRepository;
use Test\Ecotone\Modelling\Fixture\MultipleHandlersAtSameMethod\Basket;
use Test\Ecotone\Modelling\Fixture\NamedEvent\AddGuest;
use Test\Ecotone\Modelling\Fixture\NamedEvent\GuestBookRepository;
use Test\Ecotone\Modelling\Fixture\NamedEvent\GuestViewer;
use Test\Ecotone\Modelling\Fixture\NamedEvent\RegisterBook;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlacedConverter;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrderConverter;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\AddUserId\AddUserIdService;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\LoggingService;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\OrderErrorHandler;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\OrderRepository;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentStandardRepository;
use Test\Ecotone\Modelling\Fixture\Renter\CreateAppointmentCommand;
use Test\Ecotone\Modelling\Fixture\Renter\RentCalendar;
use Test\Ecotone\Modelling\Fixture\RepositoryShortcut\Twitter;
use Test\Ecotone\Modelling\Fixture\RepositoryShortcut\TwitterRepository;
use Test\Ecotone\Modelling\Fixture\RepositoryShortcut\TwitterService;
use Test\Ecotone\Modelling\Fixture\SimplifiedAggregate\IdGenerator;
use Test\Ecotone\Modelling\Fixture\SimplifiedAggregate\SimplifiedAggregateRepository;
use Test\Ecotone\Modelling\Fixture\TwoSagas\Bookkeeping;
use Test\Ecotone\Modelling\Fixture\TwoSagas\OrderWasPaid;
use Test\Ecotone\Modelling\Fixture\TwoSagas\OrderWasPlaced;
use Test\Ecotone\Modelling\Fixture\TwoSagas\Shipment;
use Test\Ecotone\Modelling\Fixture\TwoSagas\TwoSagasRepository;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class ModellingBehatMigrationTest extends TestCase
{
    public function test_order_aggregate_with_shipping_address_and_notifications(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate',
            [
                OrderNotificator::class => new OrderNotificator(),
                InMemoryStandardRepository::class => InMemoryStandardRepository::createEmpty(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->send(CreateOrderCommand::createWith('1', 20, 'London 12th street'));
        $this->assertCount(1, $queryBus->sendWithRouting('getOrderNotifications', []));
        $this->assertEquals('London 12th street', $queryBus->send(GetShippingAddressQuery::create(1)));

        $commandBus->send(ChangeShippingAddressCommand::create('1', 0, 'London 13th street'));
        $this->assertEquals('London 13th street', $queryBus->send(GetShippingAddressQuery::create(1)));
        $this->assertCount(2, $queryBus->sendWithRouting('getOrderNotifications', []));

        $this->assertEquals(
            20,
            $queryBus->sendWithRouting('get_order_amount_channel', addslashes(serialize(GetOrderAmountQuery::createWith(1))), MediaType::APPLICATION_X_PHP_SERIALIZED)
        );
    }

    public function test_reorder_product_increases_amount(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate',
            [
                OrderNotificator::class => new OrderNotificator(),
                InMemoryStandardRepository::class => InMemoryStandardRepository::createEmpty(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->send(CreateOrderCommand::createWith('1', 20, 'London 12th street'));
        $commandBus->send(CreateOrderCommand::createWith('1', 30, 'London 52th street'));

        $this->assertEquals(
            50,
            $queryBus->sendWithRouting('get_order_amount_channel', addslashes(serialize(GetOrderAmountQuery::createWith(1))), MediaType::APPLICATION_X_PHP_SERIALIZED)
        );
    }

    public function test_rent_appointment(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\Renter',
            [
                RentCalendar::class => new RentCalendar(),
                AppointmentStandardRepository::class => AppointmentStandardRepository::createEmpty(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->send(new CreateAppointmentCommand('123', 100));
        $this->assertTrue($queryBus->sendWithRouting('doesCalendarContainAppointments', '123'));
    }

    public function test_order_product_using_service(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\Order',
            [
                \Test\Ecotone\Modelling\Fixture\Order\OrderService::class => new \Test\Ecotone\Modelling\Fixture\Order\OrderService(),
                OrderWasPlacedConverter::class => new OrderWasPlacedConverter(),
                PlaceOrderConverter::class => new PlaceOrderConverter(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('order.register', new PlaceOrder('milk'));
        $this->assertEquals([], $queryBus->sendWithRouting('order.getOrders', []));

        $messagingSystem->run('orders');
        $this->assertEquals([new PlaceOrder('milk')], $queryBus->sendWithRouting('order.getOrders', []));
        $this->assertEmpty($queryBus->sendWithRouting('order.getNotifiedOrders', []));

        $messagingSystem->run('orders');
        $this->assertEquals(['milk'], $queryBus->sendWithRouting('order.getNotifiedOrders', []));
    }

    public function test_order_product_using_aggregate(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\OrderAggregate',
            [
                OrderRepository::class => OrderRepository::createEmpty(),
                AddUserIdService::class => new AddUserIdService(),
                OrderErrorHandler::class => new OrderErrorHandler(),
                LoggingService::class => new LoggingService(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('order.register', new PlaceOrder('milk'));

        $aggregateFound = true;
        try {
            $queryBus->sendWithRouting('order.getOrder', ['orderId' => 'milk']);
        } catch (AggregateNotFoundException $exception) {
            $aggregateFound = false;
        }
        $this->assertFalse($aggregateFound);

        $messagingSystem->run('orders');
        $this->assertNotNull($queryBus->sendWithRouting('order.getOrder', ['orderId' => 'milk']));
        $this->assertEquals(0, $queryBus->sendWithRouting('order.wasNotified', ['orderId' => 'milk']));

        $messagingSystem->run('orders');
        $this->assertEquals(1, $queryBus->sendWithRouting('order.wasNotified', ['orderId' => 'milk']));
        $this->assertEquals(0, count($queryBus->sendWithRouting('getLogs', [])));

        $messagingSystem->run('orders');
        $this->assertEquals(1, count($queryBus->sendWithRouting('getLogs', [])));
    }

    public function test_price_calculation_with_interceptors(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate',
            [
                ProductExchanger::class => new ProductExchanger(),
                AddVatService::class => new AddVatService(),
                AddFranchiseMargin::class => new AddFranchiseMargin(),
                ShopRepository::class => ShopRepository::createEmpty(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('shop.register', ['shopId' => 1, 'margin' => 20], MediaType::APPLICATION_X_PHP_ARRAY);

        // (milk price (100) + shop margin (20) + franchise margin (10)) * vat (2.0)
        $this->assertEquals(260, $queryBus->sendWithRouting('shop.calculatePrice', ['shopId' => 1, 'productType' => 'milk']));
    }

    public function test_storing_logs_with_before_after_interceptors_for_command_handlers(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate',
            [
                LoggerRepository::class => new LoggerRepository(),
                NotificationService::class => new NotificationService(),
                HasEnoughPermissions::class => new HasEnoughPermissions(),
                AddNotificationTimestamp::class => new AddNotificationTimestamp(),
                AddExecutorId::class => new AddExecutorId(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('changeCurrentTime', '2020-02-02 12:00:00');
        $commandBus->sendWithRouting('changeExecutorId', 'Johny');
        $commandBus->sendWithRouting('log', ['loggerId' => 1, 'data' => 'User logged in']);

        $this->assertEquals(
            [
                'event' => new EventWasLogged(['data' => 'User logged in', 'executorId' => 'Johny', 'loggerId' => 1]),
                'happenedAt' => '2020-02-02 12:00:00',
            ],
            $queryBus->sendWithRouting('getLastLog', [])
        );

        $commandBus->sendWithRouting('changeCurrentTime', '2020-02-02 12:10:00');
        $commandBus->sendWithRouting('log', ['loggerId' => 1, 'data' => 'Another User logged in']);

        $this->assertEquals(
            [
                'event' => new EventWasLogged(['data' => 'Another User logged in', 'executorId' => 'Johny', 'loggerId' => 1]),
                'happenedAt' => '2020-02-02 12:10:00',
            ],
            $queryBus->sendWithRouting('getLastLog', [])
        );
    }

    public function test_storing_logs_around_interceptor_for_command_aggregate(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate',
            [
                LoggerRepository::class => new LoggerRepository(),
                NotificationService::class => new NotificationService(),
                HasEnoughPermissions::class => new HasEnoughPermissions(),
                AddNotificationTimestamp::class => new AddNotificationTimestamp(),
                AddExecutorId::class => new AddExecutorId(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);

        $commandBus->sendWithRouting('changeCurrentTime', '2020-02-02 12:00:00');
        $commandBus->sendWithRouting('changeExecutorId', 'Johny');
        $commandBus->sendWithRouting('log', ['loggerId' => 1, 'data' => 'User logged in']);

        $commandBus->sendWithRouting('changeExecutorId', 'Franco');

        $exceptionThrown = false;
        try {
            $commandBus->sendWithRouting('log', ['loggerId' => 1, 'data' => 'Another User logged in']);
        } catch (InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'User was allowed to store logs on someones else stream');
    }

    public function test_storing_logs_with_before_after_interceptors_for_event_handlers(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate',
            [
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\LoggerRepository::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\LoggerRepository(),
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\NotificationService::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\NotificationService(),
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\VerifyAccessToSavingLogs\HasEnoughPermissions::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\VerifyAccessToSavingLogs\HasEnoughPermissions(),
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddNotificationTimestamp\AddNotificationTimestamp::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddNotificationTimestamp\AddNotificationTimestamp(),
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddExecutorId\AddExecutorId::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddExecutorId\AddExecutorId(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $eventBus = $messagingSystem->getGatewayByName(EventBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('changeCurrentTime', '2020-02-02 12:00:00');
        $commandBus->sendWithRouting('changeExecutorId', 'Johny');
        $eventBus->publishWithRouting('order.was_created', ['loggerId' => 1, 'data' => 'Milk was bought']);

        $this->assertEquals(
            [
                'event' => new EventWasLogged(['data' => 'Milk was bought', 'executorId' => 'Johny', 'loggerId' => 1]),
                'happenedAt' => '2020-02-02 12:00:00',
            ],
            $queryBus->sendWithRouting('getLastLog', [])
        );

        $commandBus->sendWithRouting('changeCurrentTime', '2020-02-02 12:10:00');
        $eventBus->publishWithRouting('order.was_created', ['loggerId' => 1, 'data' => 'Ham was bought']);

        $this->assertEquals(
            [
                'event' => new EventWasLogged(['data' => 'Ham was bought', 'executorId' => 'Johny', 'loggerId' => 1]),
                'happenedAt' => '2020-02-02 12:10:00',
            ],
            $queryBus->sendWithRouting('getLastLog', [])
        );
    }

    public function test_storing_logs_around_interceptor_for_event_aggregate(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate',
            [
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\LoggerRepository::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\LoggerRepository(),
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\NotificationService::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\NotificationService(),
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\VerifyAccessToSavingLogs\HasEnoughPermissions::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\VerifyAccessToSavingLogs\HasEnoughPermissions(),
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddNotificationTimestamp\AddNotificationTimestamp::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddNotificationTimestamp\AddNotificationTimestamp(),
                \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddExecutorId\AddExecutorId::class => new \Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddExecutorId\AddExecutorId(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $eventBus = $messagingSystem->getGatewayByName(EventBus::class);

        $commandBus->sendWithRouting('changeCurrentTime', '2020-02-02 12:00:00');
        $commandBus->sendWithRouting('changeExecutorId', 'Johny');
        $eventBus->publishWithRouting('order.was_created', ['loggerId' => 1, 'data' => 'Milk was bought']);

        $commandBus->sendWithRouting('changeExecutorId', 'Franco');

        $exceptionThrown = false;
        try {
            $eventBus->publishWithRouting('order.was_created', ['loggerId' => 1, 'data' => 'Ham was bought']);
        } catch (InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'User was allowed to store logs on someones else stream');
    }

    public function test_metadata_propagation(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\MetadataPropagating',
            [
                new \Test\Ecotone\Modelling\Fixture\MetadataPropagating\OrderService(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('placeOrder', [], MediaType::APPLICATION_X_PHP_ARRAY, ['token' => 123]);

        $headers = $queryBus->sendWithRouting('getNotificationHeaders', []);
        $this->assertEquals(123, $headers['token']);
    }

    public function test_message_id_should_not_be_propagated(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\MetadataPropagating',
            [
                new \Test\Ecotone\Modelling\Fixture\MetadataPropagating\OrderService(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('placeOrder', [], MediaType::APPLICATION_X_PHP_ARRAY, ['id' => 'c7330d16-0e67-4dac-a14e-a70ffabfeb06']);

        $headers = $queryBus->sendWithRouting('getNotificationHeaders', []);
        $this->assertNotEquals('c7330d16-0e67-4dac-a14e-a70ffabfeb06', $headers['id']);
    }

    public function test_override_propagated_headers(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\MetadataPropagating',
            [
                new \Test\Ecotone\Modelling\Fixture\MetadataPropagating\OrderService(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('setCustomNotificationHeaders', [], MediaType::APPLICATION_X_PHP_ARRAY, ['token' => 1234]);
        $commandBus->sendWithRouting('placeOrder', [], MediaType::APPLICATION_X_PHP_ARRAY, ['token' => 123]);

        $headers = $queryBus->sendWithRouting('getNotificationHeaders', []);
        $this->assertEquals(1234, $headers['token']);
    }

    public function test_override_headers_on_exception(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\MetadataPropagating',
            [
                new \Test\Ecotone\Modelling\Fixture\MetadataPropagating\OrderService(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('placeOrder', [], MediaType::APPLICATION_X_PHP_ARRAY, ['token' => 123]);

        try {
            $commandBus->sendWithRouting('failAction', [], MediaType::APPLICATION_X_PHP_ARRAY, ['token' => 1111]);
        } catch (Exception $e) {
        }

        $commandBus->sendWithRouting('placeOrder', [], MediaType::APPLICATION_X_PHP_ARRAY);

        $headers = $queryBus->sendWithRouting('getNotificationHeaders', []);
        $this->assertArrayNotHasKey('token', $headers);
    }

    public function test_metadata_propagation_for_multiple_endpoints(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints',
            [
                new \Test\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints\OrderService(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('placeOrder', [], MediaType::APPLICATION_X_PHP_ARRAY, ['token' => 123]);

        $headers = $queryBus->sendWithRouting('getNotificationHeaders', []);
        $this->assertEquals(123, $headers['token']);

        $messagingSystem->run('notifications');

        $headers = $queryBus->sendWithRouting('getNotificationHeaders', []);
        $this->assertEquals(123, $headers['token']);
    }

    public function test_presend_interceptor_for_aggregate(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\InterceptingAggregate',
            [
                new AddCurrentUserId(),
                BasketRepository::createEmpty(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('addCurrentUserId', '123');

        $commandBus->sendWithRouting('basket.add', ['item' => 'milk'], MediaType::APPLICATION_X_PHP_ARRAY);
        $result = $queryBus->sendWithRouting('basket.get', ['item' => 'milk']);
        $this->assertContains('milk', $result);

        $commandBus->sendWithRouting('basket.add', ['item' => 'cheese'], MediaType::APPLICATION_X_PHP_ARRAY);
        $result = $queryBus->sendWithRouting('basket.get', ['item' => 'cheese']);
        $this->assertContains('cheese', $result);
    }

    public function test_presend_interceptor_for_aggregate_using_attributes(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes',
            [
                new AddMetadataService(),
                \Test\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes\BasketRepository::createEmpty(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('addCurrentUserId', '123');

        $commandBus->sendWithRouting('basket.add', ['item' => 'milk'], MediaType::APPLICATION_X_PHP_ARRAY);
        $result = $queryBus->sendWithRouting('basket.get', []);
        $this->assertEquals('true', $result['isRegistration']);
        $this->assertEquals('basket.add', $result['handlerInfo']);

        $commandBus->sendWithRouting('basket.add', ['item' => 'cheese'], MediaType::APPLICATION_X_PHP_ARRAY);
        $result = $queryBus->sendWithRouting('basket.get', []);
        $this->assertEquals('false', $result['isRegistration']);
        $this->assertEquals('basket.add', $result['handlerInfo']);
    }

    public function test_command_handler_distribution(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\DistributedCommandHandler',
            [
                new ShoppingCenter(),
            ]
        );

        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);
        $distributionEntrypoint = $messagingSystem->getGatewayByName(DistributionEntrypoint::class);

        $distributionEntrypoint->distribute('pizza', [], 'command', ShoppingCenter::SHOPPING_BUY, MediaType::TEXT_PLAIN);

        $this->assertEquals(1, $queryBus->sendWithRouting(ShoppingCenter::COUNT_BOUGHT_GOODS, []));
    }

    public function test_event_handler_distribution(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\DistributedEventHandler',
            [
                new ShoppingRecord(),
            ]
        );

        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);
        $distributionEntrypoint = $messagingSystem->getGatewayByName(DistributionEntrypoint::class);

        $distributionEntrypoint->distribute('pizza', [], 'event', ShoppingRecord::ORDER_WAS_MADE, MediaType::TEXT_PLAIN);

        $this->assertEquals(1, $queryBus->sendWithRouting(ShoppingRecord::COUNT_BOUGHT_GOODS, []));
    }

    public function test_multiple_handlers_at_same_method(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\MultipleHandlersAtSameMethod',
            [
                new Basket(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('basket.add', ['item' => 'milk'], MediaType::APPLICATION_X_PHP_ARRAY);
        $result = $queryBus->sendWithRouting('basket.get', ['item' => 'milk']);
        $this->assertContains('milk', $result);

        $commandBus->sendWithRouting('basket.add', ['item' => 'cheese'], MediaType::APPLICATION_X_PHP_ARRAY);
        $commandBus->sendWithRouting('basket.removeLast', [], MediaType::APPLICATION_X_PHP_ARRAY);
        $result = $queryBus->sendWithRouting('basket.get', ['item' => 'milk']);
        $this->assertContains('milk', $result);
    }

    public function test_aggregate_with_internal_event_recorder(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder',
            [
                new JobRepository(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->send(new StartJob('1'));
        $this->assertTrue($queryBus->sendWithRouting('job.isInProgress', ['id' => '1']));

        $commandBus->send(new FinishJob('1'));
        $this->assertFalse($queryBus->sendWithRouting('job.isInProgress', ['id' => '1']));
    }

    public function test_publish_named_events_from_aggregate(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\NamedEvent',
            [
                new GuestViewer(),
                GuestBookRepository::createEmpty(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->send(new RegisterBook('1'));
        $commandBus->send(new AddGuest('1', 'Frank'));

        $this->assertEquals(['Frank'], $queryBus->sendWithRouting(GuestViewer::BOOK_GET_GUESTS, '1'));
    }

    public function test_two_sagas_handling_same_events(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\TwoSagas',
            [
                TwoSagasRepository::createEmpty(),
            ]
        );

        $eventBus = $messagingSystem->getGatewayByName(EventBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $eventBus->publish(new OrderWasPlaced('5'));
        $this->assertEquals('awaitingPayment', $queryBus->sendWithRouting(Bookkeeping::GET_BOOKING_STATUS, ['orderId' => '5']));
        $this->assertEquals('awaitingPayment', $queryBus->sendWithRouting(Shipment::GET_SHIPMENT_STATUS, ['orderId' => '5']));

        $eventBus->publish(new OrderWasPaid('5'));
        $this->assertEquals('paid', $queryBus->sendWithRouting(Bookkeeping::GET_BOOKING_STATUS, ['orderId' => '5']));
        $this->assertEquals('shipped', $queryBus->sendWithRouting(Shipment::GET_SHIPMENT_STATUS, ['orderId' => '5']));
    }

    public function test_two_asynchronous_sagas_handling_same_events(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas',
            [
                \Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas\TwoSagasRepository::createEmpty(),
            ]
        );

        $eventBus = $messagingSystem->getGatewayByName(EventBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $eventBus->publish(new \Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas\OrderWasPlaced('5'));
        $messagingSystem->run('asynchronous_channel');
        $messagingSystem->run('asynchronous_channel');

        $this->assertEquals('awaitingPayment', $queryBus->sendWithRouting(Bookkeeping::GET_BOOKING_STATUS, ['orderId' => '5']));
        $this->assertEquals('awaitingPayment', $queryBus->sendWithRouting(Shipment::GET_SHIPMENT_STATUS, ['orderId' => '5']));

        $eventBus->publish(new \Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas\OrderWasPaid('5'));
        $messagingSystem->run('asynchronous_channel');
        $messagingSystem->run('asynchronous_channel');

        $this->assertEquals('paid', $queryBus->sendWithRouting(Bookkeeping::GET_BOOKING_STATUS, ['orderId' => '5']));
        $this->assertEquals('shipped', $queryBus->sendWithRouting(Shipment::GET_SHIPMENT_STATUS, ['orderId' => '5']));
    }

    public function test_simplified_aggregate(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\SimplifiedAggregate',
            [
                new IdGenerator(),
                SimplifiedAggregateRepository::class => SimplifiedAggregateRepository::createEmpty(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->sendWithRouting('aggregate.create');
        $commandBus->sendWithRouting('aggregate.enable', ['id' => 1]);
        $this->assertTrue($queryBus->sendWithRouting('aggregate.isEnabled', ['id' => 1]));
    }

    public function test_repository_shortcut(): void
    {
        $twitterRepository = new \Test\Ecotone\Modelling\Fixture\RepositoryShortcut\Infrastructure\TwitterRepository();
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\RepositoryShortcut',
            [
                $twitterRepository,
            ]
        );

        $repoGateway = $messagingSystem->getGatewayByName(TwitterRepository::class);
        $this->assertNull($repoGateway->findTwitter('123'));

        $repoGateway->save(new Twitter('123', 'bla'));
        $twitter = $repoGateway->getTwitter('123');
        $this->assertEquals('bla', $twitter->getContent());

        $twitterService = $messagingSystem->getGatewayByName(TwitterService::class);
        $this->assertEquals('bla', $twitterService->getContent('123'));

        $twitterService->changeContent('123', 'ha!');
        $twitter = $repoGateway->getTwitter('123');
        $this->assertEquals('ha!', $twitter->getContent());
        $this->assertEquals('ha!', $twitterService->getContent('123'));
    }

    public function test_event_sourcing_repository_shortcut(): void
    {
        $twitterRepository = new \Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut\Infrastructure\TwitterRepository();
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut',
            [
                $twitterRepository,
            ]
        );

        $repoGateway = $messagingSystem->getGatewayByName(\Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut\TwitterRepository::class);
        $this->assertNull($repoGateway->findTwitter('123'));

        $repoGateway->save('123', 0, [new TwitWasCreated('123', 'bla')]);
        $twitter = $repoGateway->getTwitter('123');
        $this->assertEquals('bla', $twitter->getContent());

        $twitterService = $messagingSystem->getGatewayByName(\Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut\TwitterService::class);
        $this->assertEquals('bla', $twitterService->getContent('123'));

        $twitterService->changeContent('123', 'ha!');
        $twitter = $repoGateway->getTwitter('123');
        $this->assertEquals('ha!', $twitter->getContent());
        $this->assertEquals('ha!', $twitterService->getContent('123'));
    }

    public function test_aggregate_with_generated_id(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation',
            [
                new UserRepository(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);

        $result = $commandBus->sendWithRouting('user.create', new CreateUser('Johny'));
        $this->assertNotNull($result);
    }

    public function test_aggregate_id_from_method(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\AggregateIdFromMethod',
            [
                new \Test\Ecotone\Modelling\Fixture\AggregateIdFromMethod\UserRepository(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);
        $queryBus = $messagingSystem->getGatewayByName(QueryBus::class);

        $commandBus->send(new \Test\Ecotone\Modelling\Fixture\AggregateIdFromMethod\CreateUser('1', 'johny'));
        $this->assertEquals('johny', $queryBus->sendWithRouting('user.getName', metadata: [AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER => '1']));
    }

    public function test_late_aggregate_id_assignment_with_method(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignationWithAggregateIdFromMethod',
            [
                new \Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignationWithAggregateIdFromMethod\UserRepository(),
            ]
        );

        $commandBus = $messagingSystem->getGatewayByName(CommandBus::class);

        $result = $commandBus->sendWithRouting('user.create', new \Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignationWithAggregateIdFromMethod\CreateUser('Johny'));
        $this->assertNotNull($result);
    }

    private function bootstrapForNamespace(string $namespace, array $objects): \Ecotone\Messaging\Config\ConfiguredMessagingSystem
    {
        return EcotoneLite::bootstrap(
            [],
            InMemoryPSRContainer::createFromObjects($objects),
            ServiceConfiguration::createWithAsynchronicityOnly()
                ->withEnvironment('prod')
                ->withFailFast(false)
                ->withNamespaces([$namespace]),
            pathToRootCatalog: __DIR__ . '/../../..'
        );
    }
}
