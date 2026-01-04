<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\Consumer\InMemory\InMemoryConsumerPositionTracker;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Event;
use Ecotone\Projecting\Attribute\ProjectionV2;
use Ecotone\Projecting\Attribute\Streaming;
use Ecotone\Projecting\EventStoreAdapter\EventStreamingChannelAdapter;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class EventStreamingProjectionTest extends TestCase
{
    public function test_event_streaming_projection_consuming_from_streaming_channel(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        // Given a projection that consumes from streaming channel
        $projection = new #[ProjectionV2('user_projection'), Streaming('streaming_channel')] class {
            public array $projectedUsers = [];

            #[EventHandler]
            public function onUserCreated(UserCreated $event): void
            {
                $this->projectedUsers[$event->id] = $event->name;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class, UserCreated::class],
            [$projection, ConsumerPositionTracker::class => $positionTracker],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->withNamespaces(['Test\Ecotone\Projecting'])
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('streaming_channel', conversionMediaType: MediaType::createApplicationXPHP()),
                ])
        );

        // When events are sent directly to the streaming channel (bypassing gateway to avoid serialization)
        $channel = $ecotone->getMessageChannel('streaming_channel');
        $channel->send(MessageBuilder::withPayload(new UserCreated('user-1', 'John Doe'))
            ->setHeader(MessageHeaders::TYPE_ID, UserCreated::class)
            ->setContentType(MediaType::createApplicationXPHP())
            ->build());
        $channel->send(MessageBuilder::withPayload(new UserCreated('user-2', 'Jane Smith'))
            ->setHeader(MessageHeaders::TYPE_ID, UserCreated::class)
            ->setContentType(MediaType::createApplicationXPHP())
            ->build());

        // Then the projection should not have projected yet (polling mode)
        $this->assertCount(0, $projection->projectedUsers);

        // When we run the projection consumer (process 2 messages)
        $ecotone->run('user_projection', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 2));

        // Then the projection should have projected the events
        $this->assertCount(2, $projection->projectedUsers);
        $this->assertEquals('John Doe', $projection->projectedUsers['user-1']);
        $this->assertEquals('Jane Smith', $projection->projectedUsers['user-2']);
    }

    public function test_event_streaming_projection_with_multiple_event_handlers_routed_by_name(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        // Given a projection with two event handlers routed by event names
        $projection = new #[ProjectionV2('order_projection'), Streaming('streaming_channel')] class {
            public array $createdOrders = [];
            public array $completedOrders = [];

            #[EventHandler('order.created')]
            public function onOrderCreated(array $event): void
            {
                $this->createdOrders[] = $event;
            }

            #[EventHandler('order.completed')]
            public function onOrderCompleted(array $event): void
            {
                $this->completedOrders[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection, ConsumerPositionTracker::class => $positionTracker],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->withNamespaces(['Test\Ecotone\Projecting'])
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('streaming_channel', conversionMediaType: MediaType::createApplicationXPHP()),
                ])
        );

        // When events are sent with different routing keys
        $channel = $ecotone->getMessageChannel('streaming_channel');
        $channel->send(MessageBuilder::withPayload(['orderId' => 'order-1', 'amount' => 100])
            ->setHeader(MessageHeaders::TYPE_ID, 'order.created')
            ->setContentType(MediaType::createApplicationXPHP())
            ->build());
        $channel->send(MessageBuilder::withPayload(['orderId' => 'order-2', 'amount' => 200])
            ->setHeader(MessageHeaders::TYPE_ID, 'order.created')
            ->setContentType(MediaType::createApplicationXPHP())
            ->build());
        $channel->send(MessageBuilder::withPayload(['orderId' => 'order-1', 'completedAt' => '2024-01-01'])
            ->setHeader(MessageHeaders::TYPE_ID, 'order.completed')
            ->setContentType(MediaType::createApplicationXPHP())
            ->build());

        // Then the projection should not have projected yet (polling mode)
        $this->assertCount(0, $projection->createdOrders);
        $this->assertCount(0, $projection->completedOrders);

        // When we run the projection consumer (process 3 messages)
        $ecotone->run('order_projection', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 3));

        // Then the projection should have routed events to correct handlers
        $this->assertCount(2, $projection->createdOrders);
        $this->assertCount(1, $projection->completedOrders);
        $this->assertEquals('order-1', $projection->createdOrders[0]['orderId']);
        $this->assertEquals('order-2', $projection->createdOrders[1]['orderId']);
        $this->assertEquals('order-1', $projection->completedOrders[0]['orderId']);
    }

    public function test_event_streaming_projection_with_event_store_channel_adapter(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        // Given a projection that consumes from streaming channel
        $projection = new #[ProjectionV2('product_projection'), Streaming('event_stream')] class {
            public array $projectedProducts = [];

            #[EventHandler]
            public function onProductRegistered(ProductRegistered $event): void
            {
                $this->projectedProducts[$event->productId] = ['price' => $event->price];
            }

            #[EventHandler]
            public function onProductPriceChanged(ProductPriceChanged $event): void
            {
                $this->projectedProducts[$event->productId]['price'] = $event->newPrice;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$projection::class, ProductRegistered::class, ProductPriceChanged::class],
            containerOrAvailableServices: [$projection, ConsumerPositionTracker::class => $positionTracker],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->withNamespaces(['Test\Ecotone\Projecting'])
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('event_stream', conversionMediaType: MediaType::createApplicationXPHP()),
                    EventStreamingChannelAdapter::create(
                        streamChannelName: 'event_stream',
                        endpointId: 'event_store_feeder',
                        fromStream: 'product_stream'
                    ),
                    PollingMetadata::create('product_projection')->withTestingSetup(),
                ])
        );

        // When events are appended to the stream source (simulating event sourcing aggregate)
        $ecotone->withEventStream('product_stream', [
            Event::create(new ProductRegistered('product-1', 100), [MessageHeaders::EVENT_AGGREGATE_ID => 'product-1']),
            Event::create(new ProductPriceChanged('product-1', 150), [MessageHeaders::EVENT_AGGREGATE_ID => 'product-1']),
            Event::create(new ProductRegistered('product-2', 200), [MessageHeaders::EVENT_AGGREGATE_ID => 'product-2']),
        ]);

        // Then the projection should not have projected yet (polling mode)
        $this->assertCount(0, $projection->projectedProducts);

        // When we run the event store feeder (polls event store and pushes to streaming channel)
        $ecotone->run('event_store_feeder', ExecutionPollingMetadata::createWithTestingSetup());

        // When we run the projection consumer (process 3 messages)
        $ecotone->run('product_projection', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 3));

        // Then the projection should have projected the events
        $this->assertCount(2, $projection->projectedProducts);
        $this->assertEquals(150, $projection->projectedProducts['product-1']['price']);
        $this->assertEquals(200, $projection->projectedProducts['product-2']['price']);
    }

    public function test_two_event_streaming_projections_consuming_from_same_channel_separately(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        // Given two projections consuming from the same streaming channel
        $productListProjection = new #[ProjectionV2('product_list_projection'), Streaming('event_stream')] class {
            public array $productList = [];

            #[EventHandler]
            public function onProductRegistered(ProductRegistered $event): void
            {
                $this->productList[] = $event->productId;
            }
        };

        $productPriceProjection = new #[ProjectionV2('product_price_projection'), Streaming('event_stream')] class {
            public array $productPrices = [];

            #[EventHandler]
            public function onProductRegistered(ProductRegistered $event): void
            {
                $this->productPrices[$event->productId] = $event->price;
            }

            #[EventHandler]
            public function onProductPriceChanged(ProductPriceChanged $event): void
            {
                $this->productPrices[$event->productId] = $event->newPrice;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$productListProjection::class, $productPriceProjection::class, ProductRegistered::class, ProductPriceChanged::class],
            containerOrAvailableServices: [$productListProjection, $productPriceProjection, ConsumerPositionTracker::class => $positionTracker],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->withNamespaces(['Test\Ecotone\Projecting'])
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('event_stream', conversionMediaType: MediaType::createApplicationXPHP()),
                    EventStreamingChannelAdapter::create(
                        streamChannelName: 'event_stream',
                        endpointId: 'event_store_feeder',
                        fromStream: 'product_stream'
                    ),
                    PollingMetadata::create('product_list_projection')->withTestingSetup(),
                    PollingMetadata::create('product_price_projection')->withTestingSetup(),
                ])
        );

        // When events are appended to the stream source
        $ecotone->withEventStream('product_stream', [
            Event::create(new ProductRegistered('product-1', 100), [MessageHeaders::EVENT_AGGREGATE_ID => 'product-1']),
            Event::create(new ProductPriceChanged('product-1', 150), [MessageHeaders::EVENT_AGGREGATE_ID => 'product-1']),
            Event::create(new ProductRegistered('product-2', 200), [MessageHeaders::EVENT_AGGREGATE_ID => 'product-2']),
        ]);

        // Then both projections should not have projected yet (polling mode)
        $this->assertCount(0, $productListProjection->productList);
        $this->assertCount(0, $productPriceProjection->productPrices);

        // When we run the event store feeder (polls event store and pushes to streaming channel)
        $ecotone->run('event_store_feeder', ExecutionPollingMetadata::createWithTestingSetup());

        // When we run only the first projection consumer
        $ecotone->run('product_list_projection', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 3));

        // Then only the first projection should have projected
        $this->assertCount(2, $productListProjection->productList);
        $this->assertEquals(['product-1', 'product-2'], $productListProjection->productList);
        $this->assertCount(0, $productPriceProjection->productPrices);

        // When we run the second projection consumer
        $ecotone->run('product_price_projection', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 3));

        // Then both projections should have projected independently
        $this->assertCount(2, $productListProjection->productList);
        $this->assertCount(2, $productPriceProjection->productPrices);
        $this->assertEquals(150, $productPriceProjection->productPrices['product-1']);
        $this->assertEquals(200, $productPriceProjection->productPrices['product-2']);
    }

    public function test_event_driven_projection_combined_with_event_streaming_projection(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        // Given an event-driven projection (catches up from stream when triggered)
        $eventDrivenProjection = new #[ProjectionV2('event_driven_product_count')] class {
            public int $productCount = 0;

            #[EventHandler]
            public function onProductRegistered(ProductRegistered $event): void
            {
                $this->productCount++;
            }
        };

        // Given an event streaming projection (processes events in polling mode from streaming channel)
        $eventStreamingProjection = new #[ProjectionV2('streaming_product_list'), Streaming('event_stream')] class {
            public array $productList = [];

            #[EventHandler]
            public function onProductRegistered(ProductRegistered $event): void
            {
                $this->productList[] = $event->productId;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$eventDrivenProjection::class, $eventStreamingProjection::class, ProductRegistered::class],
            containerOrAvailableServices: [$eventDrivenProjection, $eventStreamingProjection, ConsumerPositionTracker::class => $positionTracker],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->withNamespaces(['Test\Ecotone\Projecting'])
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('event_stream', conversionMediaType: MediaType::createApplicationXPHP()),
                    EventStreamingChannelAdapter::create(
                        streamChannelName: 'event_stream',
                        endpointId: 'event_store_feeder',
                        fromStream: 'product_stream'
                    ),
                    PollingMetadata::create('streaming_product_list')->withTestingSetup(),
                ])
        );

        // When events are appended to the stream source
        $ecotone->withEventStream('product_stream', [
            Event::create(new ProductRegistered('product-1', 100), [MessageHeaders::EVENT_AGGREGATE_ID => 'product-1']),
            Event::create(new ProductRegistered('product-2', 200), [MessageHeaders::EVENT_AGGREGATE_ID => 'product-2']),
        ]);

        // And event streaming projection should not have processed yet (polling mode)
        $this->assertCount(0, $eventStreamingProjection->productList);

        // When an event is published (triggers event-driven projection to catch up from stream)
        $ecotone->publishEvent(new ProductRegistered('product-3', 300));

        // Then event-driven projection should have caught up and processed all events from stream
        $this->assertEquals(2, $eventDrivenProjection->productCount);

        // And event streaming projection should still not have processed (polling mode)
        $this->assertCount(0, $eventStreamingProjection->productList);

        // When we run the event store feeder (polls stream and pushes to streaming channel)
        $ecotone->run('event_store_feeder', ExecutionPollingMetadata::createWithTestingSetup());

        // And run the event streaming projection
        $ecotone->run('streaming_product_list', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 2));

        // Then event streaming projection should have processed the events from streaming channel
        $this->assertCount(2, $eventStreamingProjection->productList);
        $this->assertEquals(['product-1', 'product-2'], $eventStreamingProjection->productList);

        // And event-driven projection count should remain the same (only processed stream events, not the trigger event)
        $this->assertEquals(2, $eventDrivenProjection->productCount);
    }
}

// Test classes
class UserCreated
{
    public function __construct(
        public string $id,
        public string $name
    ) {
    }
}

class RegisterProduct
{
    public function __construct(
        public string $productId,
        public int $price
    ) {
    }
}

class ChangeProductPrice
{
    public function __construct(
        public string $productId,
        public int $newPrice
    ) {
    }
}

class ProductRegistered
{
    public function __construct(
        public string $productId,
        public int $price
    ) {
    }
}

class ProductPriceChanged
{
    public function __construct(
        public string $productId,
        public int $newPrice
    ) {
    }
}
