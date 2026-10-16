<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\ExtensionObject\CombinedMessageChannel;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Test\ChannelTestSupport;
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
            containerOrAvailableServices: [new OrderService()],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
        );

        $ecotoneTestSupport->sendCommandWithRouting(
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

    public function test_propagating_headers_to_all_published_asynchronous_event_handlers(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OrderService::class],
            containerOrAvailableServices: [new OrderService()],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
        );

        $ecotoneTestSupport->sendCommandWithRouting(
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
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [Order::class],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
        );

        $orderId = '1';

        $this->assertArrayNotHasKey(
            AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER,
            $ecotoneTestSupport
                ->withStateFor(Order::register(new PlaceOrder($orderId)))
                ->discardRecordedMessages()
                ->sendCommandWithRouting('order.cancel_from_metadata', metadata: ['aggregate.id' => $orderId])
                ->run('orders')
                ->popRecordedEventHeaders()[0]->headers()
        );
    }

    public function test_using_aggregate_id_target_with_asynchronous_endpoint(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [Order::class],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
        );

        $orderId = '1';

        $this->assertArrayNotHasKey(
            AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER,
            $ecotoneTestSupport
                ->withStateFor(Order::register(new PlaceOrder($orderId)))
                ->discardRecordedMessages()
                ->sendCommandWithRouting('order.cancel_from_metadata', metadata: ['aggregate.id' => $orderId])
                ->run('orders')
                ->popRecordedEventHeaders()[0]->headers()
        );
    }

    public function test_using_aggregate_id_target_with_combined_channel(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [Order::class],
            extensionObjects: [
                CombinedMessageChannel::create('orders', ['outbox', 'processing']),
                SimpleMessageChannelBuilder::createQueueChannel('outbox'),
                SimpleMessageChannelBuilder::createQueueChannel('processing'),
            ],
            enabledModules: [],
        );

        $orderId = '1';

        $this->assertArrayNotHasKey(
            AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER,
            $ecotoneTestSupport
                ->withStateFor(Order::register(new PlaceOrder($orderId)))
                ->discardRecordedMessages()
                ->sendCommandWithRouting('order.cancel_from_metadata', metadata: ['aggregate.id' => $orderId])
                ->run('outbox')
                ->run('processing')
                ->popRecordedEventHeaders()[0]->headers()
        );
    }

    public function test_not_propagating_polling_metadata(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [OrderService::class],
            containerOrAvailableServices: [new OrderService()],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
        );

        $ecotoneTestSupport->sendCommandWithRouting('sendNotificationViaCommandBus');

        $ecotoneTestSupport->run('orders', ExecutionPollingMetadata::createWithTestingSetup(1));

        $this->assertArrayNotHasKey(
            MessageHeaders::CONSUMER_POLLING_METADATA,
            ChannelTestSupport::of($ecotoneTestSupport)->getMessageChannel('orders')->receive()->getHeaders()->headers()
        );
    }

    public function test_propagating_headers_to_all_published_asynchronous_event_handlers_extended(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [Basket::class, ItemInventory::class],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('basket'),
                SimpleMessageChannelBuilder::createQueueChannel('itemInventory'),
            ],
            enabledModules: [],
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

        self::assertEquals([new ItemWasAddedToBasket('basket-123', 'item-123')], $ecotoneTestSupport->popRecordedEvents());

        $ecotoneTestSupport->run('basket', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertEquals([new ItemReservationCreated('item-123')], $ecotoneTestSupport->popRecordedEvents());

        $ecotoneTestSupport->run('itemInventory', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertEquals([new ItemReserved('item-123')], $ecotoneTestSupport->popRecordedEvents());
    }
}
