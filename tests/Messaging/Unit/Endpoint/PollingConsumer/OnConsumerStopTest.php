<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint\PollingConsumer;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\OnConsumerStop;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class OnConsumerStopTest extends TestCase
{
    public function test_on_consumer_stop_is_triggered_when_async_endpoint_stops(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsumerStopHandler::class, AsyncCommandHandler::class],
            [new ConsumerStopHandler(), new AsyncCommandHandler()],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async_channel'))
                ->withEnabledModulePackageNames([])
        );

        // Send a command to async channel
        $ecotoneLite->sendCommandWithRouting('async.command', 'test');

        // Run consumer with message limit (will stop after 1 message)
        // When running by channel name, the endpoint ID in PollingMetadata is the channel name
        $ecotoneLite->run('async_channel', ExecutionPollingMetadata::createWithDefaults()->withHandledMessageLimit(1));

        // Verify OnConsumerStop was called with the channel name as endpoint ID
        $this->assertTrue($ecotoneLite->sendQueryWithRouting('consumer.wasStopped'));
        $this->assertEquals('async_channel', $ecotoneLite->sendQueryWithRouting('consumer.getStoppedEndpointId'));
    }
}

/**
 * licence Apache-2.0
 */
final class ConsumerStopHandler
{
    private bool $wasStopped = false;
    private ?string $stoppedEndpointId = null;

    #[OnConsumerStop]
    public function onStop(string $endpointId): void
    {
        $this->wasStopped = true;
        $this->stoppedEndpointId = $endpointId;
    }

    #[QueryHandler('consumer.wasStopped')]
    public function wasStopped(): bool
    {
        return $this->wasStopped;
    }

    #[QueryHandler('consumer.getStoppedEndpointId')]
    public function getStoppedEndpointId(): ?string
    {
        return $this->stoppedEndpointId;
    }
}

#[Asynchronous('async_channel')]
/**
 * licence Apache-2.0
 */
final class AsyncCommandHandler
{
    #[CommandHandler('async.command', 'async_endpoint')]
    public function handle(string $payload): void
    {
        // Just handle the command
    }
}
