<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\Consumer\InMemory\InMemoryConsumerPositionTracker;
use Ecotone\Modelling\Api\Distribution\DistributedServiceMap;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * Tests for streaming channel validation and usage restrictions
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class StreamingChannelTest extends TestCase
{
    /**
     * @TODO
     */
    public function test_streaming_channel_cannot_be_used_with_distributed_bus_as_input_channel(): void
    {
        $this->markTestSkipped('to be covered');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Streaming channels cannot be used as input channels for distributed bus');

        $handler = new class () {
            #[InternalHandler(inputChannelName: 'distributed_channel', endpointId: 'test_handler')]
            public function handle(string $payload): void
            {
            }
        };

        $sharedChannel = SimpleMessageChannelBuilder::createStreamingChannel('distributed_channel');

        EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, ConsumerPositionTracker::class => new InMemoryConsumerPositionTracker()],
            ServiceConfiguration::createWithDefaults()
                ->withServiceName('test-service')
                ->withExtensionObjects([
                    $sharedChannel,
                    DistributedServiceMap::initialize()
                        ->withServiceMapping(serviceName: 'test-service', channelName: 'distributed_channel'),
                ]),
            pathToRootCatalog: __DIR__ . '/../../',
            licenceKey: LicenceTesting::VALID_LICENCE
        );
    }
}
