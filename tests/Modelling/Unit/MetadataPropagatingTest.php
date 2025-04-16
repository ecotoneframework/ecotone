<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\CombinedMessageChannel;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\AggregateMessage;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\BasketWithReservations\AddItemToBasket;
use Test\Ecotone\Modelling\Fixture\BasketWithReservations\Basket;
use Test\Ecotone\Modelling\Fixture\BasketWithReservations\BasketCreated;
use Test\Ecotone\Modelling\Fixture\BasketWithReservations\ItemInventory;
use Test\Ecotone\Modelling\Fixture\BasketWithReservations\ItemInventoryCreated;
use Test\Ecotone\Modelling\Fixture\BasketWithReservations\ItemReservationCreated;
use Test\Ecotone\Modelling\Fixture\BasketWithReservations\ItemReserved;
use Test\Ecotone\Modelling\Fixture\BasketWithReservations\ItemWasAddedToBasket;
use Test\Ecotone\Modelling\Fixture\MetadataPropagatingWithDoubleEventHandlers\OrderService;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\Order;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class MetadataPropagatingTest extends TestCase
{
    public function test_propagating_headers_to_all_published_synchronous_event_handlers(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OrderService::class],
            containerOrAvailableServices: [new OrderService()]
        );

        $ecotoneTestSupport->sendCommandWithRoutingKey(
            'placeOrder',
            metadata: [
                'userId' => '123',
            ]
        );

        $notifications = $ecotoneTestSupport->sendQueryWithRouting('getAllNotificationHeaders');
        $this->assertCount(2, $notifications);
        $this->assertEquals('123', $notifications[0]['userId']);
        $this->assertEquals('123', $notifications[1]['userId']);
    }

    public function test_propagating_headers_to_all_published_asynchronous_event_handlers(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OrderService::class],
            containerOrAvailableServices: [new OrderService()],
            configuration: ServiceConfiguration::createWithAsynchronicityOnly()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('orders'),
                ])
        );

        $ecotoneTestSupport->sendCommandWithRoutingKey(
            'placeOrder',
            metadata: [
                'userId' => '123',
            ]
        );

        $ecotoneTestSupport->run('orders', ExecutionPollingMetadata::createWithTestingSetup(2));
        $notifications = $ecotoneTestSupport->sendQueryWithRouting('getAllNotificationHeaders');

        $this->assertCount(2, $notifications);
        $this->assertEquals('123', $notifications[0]['userId']);
        $this->assertEquals('123', $notifications[1]['userId']);
    }

    public function test_not_propagating_aggregate_headers(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting([Order::class]);

        $orderId = '1';

        /** Setting up initial state for state stored aggregate */
        $this->assertArrayNotHasKey(
            AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER,
            $ecotoneTestSupport
                ->withStateFor(Order::register(new PlaceOrder($orderId)))
                ->discardRecordedMessages()
                ->sendCommandWithRoutingKey('order.cancel_from_metadata', metadata: ['aggregate.id' => $orderId])
                ->getRecordedEventHeaders()[0]->headers()
        );
    }

    public function test_using_aggregate_id_target_with_asynchronous_endpoint(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting([Order::class], enableAsynchronousProcessing: [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
        ]);

        $orderId = '1';

        $this->assertArrayNotHasKey(
            AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER,
            $ecotoneTestSupport
                ->withStateFor(Order::register(new PlaceOrder($orderId)))
                ->discardRecordedMessages()
                ->sendCommandWithRoutingKey('order.cancel_from_metadata', metadata: ['aggregate.id' => $orderId])
                ->run('orders')
                ->getRecordedEventHeaders()[0]->headers()
        );
    }

    public function test_using_aggregate_id_target_with_combined_channel(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [Order::class],
            configuration: ServiceConfiguration::createWithAsynchronicityOnly()
                ->withExtensionObjects(
                    [
                        CombinedMessageChannel::create(
                            'orders',
                            ['outbox', 'processing']
                        ),
                    ]
                ),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('outbox'),
                SimpleMessageChannelBuilder::createQueueChannel('processing'),
            ]
        );

        $orderId = '1';

        $this->assertArrayNotHasKey(
            AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER,
            $ecotoneTestSupport
                ->withStateFor(Order::register(new PlaceOrder($orderId)))
                ->discardRecordedMessages()
                ->sendCommandWithRoutingKey('order.cancel_from_metadata', metadata: ['aggregate.id' => $orderId])
                ->run('outbox')
                ->run('processing')
                ->getRecordedEventHeaders()[0]->headers()
        );
    }

    public function test_not_propagating_polling_metadata(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OrderService::class],
            containerOrAvailableServices: [new OrderService()],
            configuration: ServiceConfiguration::createWithAsynchronicityOnly()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('orders'),
                ])
        );

        $ecotoneTestSupport->sendCommandWithRoutingKey('sendNotificationViaCommandBus');

        $ecotoneTestSupport->run('orders', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertArrayNotHasKey(
            MessageHeaders::CONSUMER_POLLING_METADATA,
            $ecotoneTestSupport->getMessageChannel('orders')->receive()->getHeaders()->headers()
        );
    }

    public function test_propagating_headers_to_all_published_asynchronous_event_handlers_extended(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [Basket::class, ItemInventory::class],
            configuration: ServiceConfiguration::createWithDefaults(),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('basket'),
                SimpleMessageChannelBuilder::createQueueChannel('itemInventory'),
            ]
        );

        $ecotoneTestSupport->withEventsFor(
            'basket-123',
            Basket::class,
            [
                new BasketCreated('basket-123'),
            ]
        );

        $ecotoneTestSupport->withEventsFor(
            'item-123',
            ItemInventory::class,
            [
                new ItemInventoryCreated('item-123'),
            ]
        );

        $ecotoneTestSupport->sendCommand(new AddItemToBasket('basket-123', 'item-123'));

        self::assertEquals([new ItemWasAddedToBasket('basket-123', 'item-123')], $ecotoneTestSupport->getRecordedEvents());

        $ecotoneTestSupport->run('basket', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertEquals([new ItemReservationCreated('item-123')], $ecotoneTestSupport->getRecordedEvents());

        $ecotoneTestSupport->run('itemInventory', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertEquals([new ItemReserved('item-123')], $ecotoneTestSupport->getRecordedEvents());
    }
}
