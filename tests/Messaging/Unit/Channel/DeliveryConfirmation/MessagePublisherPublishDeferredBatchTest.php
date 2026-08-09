<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\DeliveryConfirmation;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\MessagePublisher;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\InMemoryHighThroughputOutboundAdapter;
use Test\Ecotone\Messaging\Fixture\HighThroughputPublishing\InMemoryHighThroughputPublisherModule;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessagePublisherPublishDeferredBatchTest extends TestCase
{
    public function test_publishing_batch_delivers_whole_batch_with_single_pending_delivery(): void
    {
        $outboundAdapter = new InMemoryHighThroughputOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->publishDeferred(
            BatchMessage::constructEmpty()
                ->append('first order')
                ->append('second order', ['priority' => 5])
        );

        $this->assertCount(1, $outboundAdapter->getSentMessages());
        $batchPayload = $outboundAdapter->getSentMessages()[0]->getPayload();
        $this->assertInstanceOf(BatchMessage::class, $batchPayload);
        $this->assertSame(
            [
                ['payload' => 'first order', 'headers' => []],
                ['payload' => 'second order', 'headers' => ['priority' => 5]],
            ],
            $batchPayload->getEntries(),
        );
        $this->assertSame(0, $outboundAdapter->awaitedDeliveriesCount());

        $future->resolve();

        $this->assertSame(1, $outboundAdapter->awaitedDeliveriesCount());
    }

    public function test_publishing_empty_batch_resolves_without_sending_anything(): void
    {
        $outboundAdapter = new InMemoryHighThroughputOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->publishDeferred(BatchMessage::constructEmpty());
        $future->resolve();

        $this->assertCount(0, $outboundAdapter->getSentMessages());
    }

    private function bootstrapPublisher(InMemoryHighThroughputOutboundAdapter $outboundAdapter): MessagePublisher
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [InMemoryHighThroughputPublisherModule::class, InMemoryHighThroughputOutboundAdapter::class],
            [$outboundAdapter],
        );

        return $ecotoneLite->getGateway(InMemoryHighThroughputPublisherModule::PUBLISHER_REFERENCE);
    }
}
