<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\DeliveryConfirmation;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Channel\DeliveryConfirmation\PendingDeliveryRegistry;
use Ecotone\Messaging\Channel\DeliveryConfirmation\PublishingFailedException;
use Ecotone\Messaging\MessagePublisher;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\InMemoryHighThroughputOutboundAdapter;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\InMemoryHighThroughputPublisherModule;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessagePublisherPublishDeferredTest extends TestCase
{
    public function test_publish_deferred_fires_message_immediately_and_awaits_delivery_on_future_resolve(): void
    {
        $outboundAdapter = new InMemoryHighThroughputOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->publishDeferred('order was placed');

        $this->assertSame(['order was placed'], $outboundAdapter->getSentPayloads());
        $this->assertSame(0, $outboundAdapter->awaitedDeliveriesCount());

        $future->resolve();

        $this->assertSame(1, $outboundAdapter->awaitedDeliveriesCount());
    }

    public function test_resolving_future_twice_awaits_delivery_only_once(): void
    {
        $outboundAdapter = new InMemoryHighThroughputOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->publishDeferred('order was placed');
        $future->resolve();
        $future->resolve();

        $this->assertSame(1, $outboundAdapter->totalAwaitCalls());
    }

    public function test_resolving_future_throws_when_delivery_failed(): void
    {
        $outboundAdapter = new InMemoryHighThroughputOutboundAdapter();
        $outboundAdapter->failDeliveriesWith('broker rejected message');
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->publishDeferred('order was placed');

        $this->expectException(PublishingFailedException::class);

        $future->resolve();
    }

    public function test_publish_deferred_on_publisher_without_non_blocking_confirmation_throws_clear_exception(): void
    {
        $outboundAdapter = new InMemoryHighThroughputOutboundAdapter();
        $outboundAdapter->actAsSynchronousPublisher();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $this->expectException(PublishingFailedException::class);
        $this->expectExceptionMessageMatches('/not configured for non blocking confirmation/');

        $publisher->publishDeferred('order was placed');
    }

    public function test_metadata_passed_to_publish_deferred_lands_on_published_message(): void
    {
        $outboundAdapter = new InMemoryHighThroughputOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $publisher->publishDeferred('order was placed', metadata: ['orderId' => '123']);

        $this->assertSame('123', $outboundAdapter->getSentMessages()[0]->getHeaders()->get('orderId'));
    }

    public function test_flushing_unawaited_deliveries_awaits_only_unresolved_futures(): void
    {
        $outboundAdapter = new InMemoryHighThroughputOutboundAdapter();
        $ecotoneLite = $this->bootstrapEcotone($outboundAdapter);
        $publisher = $ecotoneLite->getGateway(InMemoryHighThroughputPublisherModule::PUBLISHER_REFERENCE);

        $resolvedFuture = $publisher->publishDeferred('first order');
        $resolvedFuture->resolve();
        $publisher->publishDeferred('second order');

        $ecotoneLite->getServiceFromContainer(PendingDeliveryRegistry::class)->flushUnawaitedDeliveries();

        $this->assertSame(2, $outboundAdapter->awaitedDeliveriesCount());
        $this->assertSame(2, $outboundAdapter->totalAwaitCalls());
    }

    private function bootstrapPublisher(InMemoryHighThroughputOutboundAdapter $outboundAdapter): MessagePublisher
    {
        return $this->bootstrapEcotone($outboundAdapter)->getGateway(InMemoryHighThroughputPublisherModule::PUBLISHER_REFERENCE);
    }

    private function bootstrapEcotone(InMemoryHighThroughputOutboundAdapter $outboundAdapter): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [InMemoryHighThroughputPublisherModule::class, InMemoryHighThroughputOutboundAdapter::class],
            [$outboundAdapter],
        );
    }
}
