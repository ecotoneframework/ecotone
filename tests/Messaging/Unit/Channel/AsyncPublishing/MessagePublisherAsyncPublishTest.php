<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\AsyncPublishing;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Channel\AsyncPublishing\AsyncPublishingRegistry;
use Ecotone\Messaging\Channel\AsyncPublishing\PublishingFailedException;
use Ecotone\Messaging\MessagePublisher;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\InMemoryAsyncOutboundAdapter;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\InMemoryAsyncPublisherModule;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessagePublisherAsyncPublishTest extends TestCase
{
    public function test_async_publish_fires_message_immediately_and_awaits_delivery_on_future_resolve(): void
    {
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->asyncPublish('order was placed');

        $this->assertSame(['order was placed'], $outboundAdapter->getSentPayloads());
        $this->assertSame(0, $outboundAdapter->awaitedDeliveriesCount());

        $future->resolve();

        $this->assertSame(1, $outboundAdapter->awaitedDeliveriesCount());
    }

    public function test_resolving_future_twice_awaits_delivery_only_once(): void
    {
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->asyncPublish('order was placed');
        $future->resolve();
        $future->resolve();

        $this->assertSame(1, $outboundAdapter->totalAwaitCalls());
    }

    public function test_resolving_future_throws_when_delivery_failed(): void
    {
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $outboundAdapter->failDeliveriesWith('broker rejected message');
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->asyncPublish('order was placed');

        $this->expectException(PublishingFailedException::class);

        $future->resolve();
    }

    public function test_async_publish_on_synchronous_publisher_throws_clear_exception(): void
    {
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $outboundAdapter->actAsSynchronousPublisher();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $this->expectException(PublishingFailedException::class);
        $this->expectExceptionMessageMatches('/not configured for asynchronous publishing/');

        $publisher->asyncPublish('order was placed');
    }

    public function test_metadata_passed_to_async_publish_lands_on_published_message(): void
    {
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $publisher->asyncPublish('order was placed', metadata: ['orderId' => '123']);

        $this->assertSame('123', $outboundAdapter->getSentMessages()[0]->getHeaders()->get('orderId'));
    }

    public function test_flushing_unawaited_deliveries_awaits_only_unresolved_futures(): void
    {
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $ecotoneLite = $this->bootstrapEcotone($outboundAdapter);
        $publisher = $ecotoneLite->getGateway(InMemoryAsyncPublisherModule::PUBLISHER_REFERENCE);

        $resolvedFuture = $publisher->asyncPublish('first order');
        $resolvedFuture->resolve();
        $publisher->asyncPublish('second order');

        $ecotoneLite->getServiceFromContainer(AsyncPublishingRegistry::class)->flushUnawaitedDeliveries();

        $this->assertSame(2, $outboundAdapter->awaitedDeliveriesCount());
        $this->assertSame(2, $outboundAdapter->totalAwaitCalls());
    }

    private function bootstrapPublisher(InMemoryAsyncOutboundAdapter $outboundAdapter): MessagePublisher
    {
        return $this->bootstrapEcotone($outboundAdapter)->getGateway(InMemoryAsyncPublisherModule::PUBLISHER_REFERENCE);
    }

    private function bootstrapEcotone(InMemoryAsyncOutboundAdapter $outboundAdapter): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [InMemoryAsyncPublisherModule::class, InMemoryAsyncOutboundAdapter::class],
            [$outboundAdapter],
        );
    }
}
