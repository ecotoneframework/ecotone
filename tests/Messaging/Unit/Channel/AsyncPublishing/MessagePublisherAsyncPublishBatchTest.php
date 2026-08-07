<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\AsyncPublishing;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\MessagePublisher;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\InMemoryAsyncOutboundAdapter;
use Test\Ecotone\Messaging\Fixture\AsyncPublishing\InMemoryAsyncPublisherModule;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessagePublisherAsyncPublishBatchTest extends TestCase
{
    public function test_publishing_batch_delivers_whole_batch_with_single_pending_delivery(): void
    {
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->asyncPublish(
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
        $outboundAdapter = new InMemoryAsyncOutboundAdapter();
        $publisher = $this->bootstrapPublisher($outboundAdapter);

        $future = $publisher->asyncPublish(BatchMessage::constructEmpty());
        $future->resolve();

        $this->assertCount(0, $outboundAdapter->getSentMessages());
    }

    private function bootstrapPublisher(InMemoryAsyncOutboundAdapter $outboundAdapter): MessagePublisher
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [InMemoryAsyncPublisherModule::class, InMemoryAsyncOutboundAdapter::class],
            [$outboundAdapter],
        );

        return $ecotoneLite->getGateway(InMemoryAsyncPublisherModule::PUBLISHER_REFERENCE);
    }
}
