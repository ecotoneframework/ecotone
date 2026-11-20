<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Execution;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Scheduled;
use Ecotone\Api\ExtensionObject\DynamicMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class ConsumerCoroutinesRefusedTest extends TestCase
{
    private const SCHEDULED_ENDPOINT_REFUSAL = 'Endpoint `report.schedule` is set to run on 2 coroutines, but it runs on a schedule instead of consuming a channel, so every coroutine would run each scheduled execution again. '
        . 'Leave out the `coroutines` option of ecotone:run, ExecutionPollingMetadata::withCoroutines() and PollingMetadata::withCoroutines() for this endpoint. '
        . 'To do its work on coroutines, let it send messages to an #[Asynchronous] handler and run that channel\'s consumer on coroutines.';
    private const STREAM_CONSUMER_REFUSAL = 'Consumer `shipments` is set to consume on 3 coroutines, but it reads %s (a Kafka topic, a RabbitMQ stream or an in-memory streaming channel, directly or through a dynamic channel; a dynamic channel with a custom receiving strategy counts as reading every stream channel of the application), where the position of the last handled message is what gets committed. '
        . 'A coroutine finishing a later message would commit past an earlier message another coroutine is still handling, so that message is never handled when the process stops, and the messages are handled out of order. '
        . 'Leave out the `coroutines` option of ecotone:run, ExecutionPollingMetadata::withCoroutines() and PollingMetadata::withCoroutines() for this consumer. '
        . 'To handle more of its messages at once: for a Kafka topic start more processes of this consumer, with at least as many partitions of the topic as processes; a RabbitMQ stream is read in order by one consumer, so let its handler send the work to an #[Asynchronous] queue channel and consume that channel on coroutines. A dynamic channel whose custom receiving strategy reads only queue channels can consume on coroutines when it receives through DynamicMessageChannelBuilder::createRoundRobin(), createRoundRobinWithDifferentChannels() or createThrottlingStrategy() naming those channels.';

    public function test_consuming_on_coroutines_passed_when_running_the_consumer_is_refused_without_a_coroutine_runtime(): void
    {
        $this->requireNoCoroutineRuntime();
        $ecotone = $this->bootstrapShipments();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Consumer `shipments` is set to consume on 4 coroutines, but this PHP has neither the openswoole nor the swoole extension loaded, so there is nothing to run coroutines on. '
            . 'Install one of the extensions for the PHP that runs the consumer, or consume one message at a time: leave out the `coroutines` option of ecotone:run, ExecutionPollingMetadata::withCoroutines() and PollingMetadata::withCoroutines(). '
            . 'To handle more messages at once without coroutines, start more processes of this consumer.'
        );

        $ecotone->run('shipments', ExecutionPollingMetadata::createWithFinishWhenNoMessages()->withCoroutines(4));
    }

    public function test_consuming_on_coroutines_of_the_polling_metadata_is_refused_without_a_coroutine_runtime(): void
    {
        $this->requireNoCoroutineRuntime();
        $ecotone = $this->bootstrapShipments(PollingMetadata::create('shipments')->withCoroutines(2));

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Consumer `shipments` is set to consume on 2 coroutines, but this PHP has neither the openswoole nor the swoole extension loaded');

        $ecotone->run('shipments', ExecutionPollingMetadata::createWithFinishWhenNoMessages());
    }

    public function test_consuming_on_coroutines_of_the_console_command_is_refused_without_a_coroutine_runtime(): void
    {
        $this->requireNoCoroutineRuntime();
        $ecotone = $this->bootstrapShipments();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Consumer `shipments` is set to consume on 3 coroutines, but this PHP has neither the openswoole nor the swoole extension loaded');

        $ecotone->runConsoleCommand('ecotone:run', ['consumerName' => 'shipments', 'coroutines' => 3, 'finishWhenNoMessages' => true]);
    }

    public function test_one_coroutine_consumes_without_a_coroutine_runtime(): void
    {
        $ecotone = $this->bootstrapShipments(PollingMetadata::create('shipments')->withCoroutines(1));
        $ecotone->sendCommandWithRouting('shipment.ship');

        $ecotone->run('shipments', ExecutionPollingMetadata::createWithFinishWhenNoMessages()->withCoroutines(1));

        $this->assertSame(1, $ecotone->sendQueryWithRouting('shipment.shipped'));
    }

    public function test_polling_metadata_with_less_than_one_coroutine_is_refused(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Consumer `shipments` cannot consume on 0 coroutines: a consumer needs at least one. '
            . 'Pass a positive number to PollingMetadata::withCoroutines(), or leave it out to consume one message at a time.'
        );

        PollingMetadata::create('shipments')->withCoroutines(0);
    }

    public function test_execution_polling_metadata_with_less_than_one_coroutine_is_refused(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'A consumer cannot consume on -1 coroutines: it needs at least one. '
            . 'Pass a positive number to ExecutionPollingMetadata::withCoroutines() or to the `coroutines` option of ecotone:run, or leave it out to consume one message at a time.'
        );

        ExecutionPollingMetadata::createWithDefaults()->withCoroutines(-1);
    }

    public function test_polling_metadata_constructed_with_less_than_one_coroutine_is_refused_when_the_application_boots(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Consumer `shipments` cannot consume on 0 coroutines: a consumer needs at least one. '
            . 'Pass a positive number as `coroutines` of PollingMetadata, or leave it out to consume one message at a time.'
        );

        $this->bootstrapShipments(new PollingMetadata('shipments', coroutines: 0));
    }

    public function test_scheduled_endpoint_is_refused_to_run_on_coroutines_passed_when_running_it(): void
    {
        $ecotone = $this->bootstrapReports();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(self::SCHEDULED_ENDPOINT_REFUSAL);

        $ecotone->run('report.schedule', ExecutionPollingMetadata::createWithTestingSetup()->withCoroutines(2));
    }

    public function test_scheduled_endpoint_is_refused_to_run_on_the_coroutines_option_of_the_console_command(): void
    {
        $ecotone = $this->bootstrapReports();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(self::SCHEDULED_ENDPOINT_REFUSAL);

        $ecotone->runConsoleCommand('ecotone:run', ['consumerName' => 'report.schedule', 'handledMessageLimit' => 1, 'coroutines' => 2]);
    }

    public function test_scheduled_endpoint_with_coroutines_in_its_polling_metadata_is_refused_when_the_application_boots(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(self::SCHEDULED_ENDPOINT_REFUSAL);

        $this->bootstrapReports(PollingMetadata::create('report.schedule')->withCoroutines(2));
    }

    public function test_consumer_of_a_stream_is_refused_to_consume_on_coroutines_passed_when_running_it(): void
    {
        $ecotone = $this->bootstrapShipments(channel: SimpleMessageChannelBuilder::createStreamingChannel('shipments'));

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(self::STREAM_CONSUMER_REFUSAL, 'a stream'));

        $ecotone->run('shipments', ExecutionPollingMetadata::createWithFinishWhenNoMessages()->withCoroutines(3));
    }

    public function test_consumer_of_a_stream_is_refused_to_consume_on_the_coroutines_option_of_the_console_command(): void
    {
        $ecotone = $this->bootstrapShipments(channel: SimpleMessageChannelBuilder::createStreamingChannel('shipments'));

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(self::STREAM_CONSUMER_REFUSAL, 'a stream'));

        $ecotone->runConsoleCommand('ecotone:run', ['consumerName' => 'shipments', 'finishWhenNoMessages' => true, 'coroutines' => 3]);
    }

    public function test_consumer_of_a_stream_with_coroutines_in_its_polling_metadata_is_refused_when_the_application_boots(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(self::STREAM_CONSUMER_REFUSAL, 'a stream'));

        $this->bootstrapShipments(
            PollingMetadata::create('shipments')->withCoroutines(3),
            SimpleMessageChannelBuilder::createStreamingChannel('shipments'),
        );
    }

    public function test_consumer_of_a_dynamic_channel_receiving_from_a_stream_is_refused_to_consume_on_coroutines_passed_when_running_it(): void
    {
        $ecotone = $this->bootstrapShipmentsOnADynamicChannel(SimpleMessageChannelBuilder::createStreamingChannel('shipments_of_tenant'));

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(self::STREAM_CONSUMER_REFUSAL, 'the stream `shipments_of_tenant`'));

        $ecotone->run('shipments', ExecutionPollingMetadata::createWithFinishWhenNoMessages()->withCoroutines(3));
    }

    public function test_consumer_of_a_dynamic_channel_receiving_from_a_stream_with_coroutines_in_its_polling_metadata_is_refused_when_the_application_boots(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(self::STREAM_CONSUMER_REFUSAL, 'the stream `shipments_of_tenant`'));

        $this->bootstrapShipmentsOnADynamicChannel(
            SimpleMessageChannelBuilder::createStreamingChannel('shipments_of_tenant'),
            PollingMetadata::create('shipments')->withCoroutines(3),
        );
    }

    public function test_consumer_of_a_dynamic_channel_receiving_from_queues_boots_with_coroutines_in_its_polling_metadata(): void
    {
        $ecotone = $this->bootstrapShipmentsOnADynamicChannel(
            SimpleMessageChannelBuilder::createQueueChannel('shipments_of_tenant'),
            PollingMetadata::create('shipments')->withCoroutines(3),
        );
        $ecotone->sendCommandWithRouting('shipment.ship');

        $ecotone->run('shipments', ExecutionPollingMetadata::createWithFinishWhenNoMessages()->withCoroutines(1));

        $this->assertSame(1, $ecotone->sendQueryWithRouting('shipment.shipped'));
    }

    public function test_consumer_of_a_dynamic_channel_receiving_from_a_dynamic_channel_over_a_stream_is_refused_when_the_application_boots(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(self::STREAM_CONSUMER_REFUSAL, 'the stream `shipments_of_region`'));

        $this->bootstrapShipmentsOnADynamicChannel(
            SimpleMessageChannelBuilder::createStreamingChannel('shipments_of_region'),
            PollingMetadata::create('shipments')->withCoroutines(3),
            DynamicMessageChannelBuilder::createRoundRobin('shipments_of_tenant', ['shipments_of_region']),
        );
    }

    public function test_consumer_of_a_throttled_dynamic_channel_receiving_from_a_stream_is_refused_when_the_application_boots(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(self::STREAM_CONSUMER_REFUSAL, 'the stream `shipments_of_tenant`'));

        $this->bootstrapShipmentsOnADynamicChannel(
            SimpleMessageChannelBuilder::createStreamingChannel('shipments_of_tenant'),
            PollingMetadata::create('shipments')->withCoroutines(3),
            dynamicChannel: DynamicMessageChannelBuilder::createThrottlingStrategy('shipments', 'shipment.isTenantServed', ['shipments_of_tenant']),
        );
    }

    public function test_consumer_of_a_throttled_dynamic_channel_receiving_from_queues_boots_with_coroutines_beside_a_stream_it_does_not_read(): void
    {
        $ecotone = $this->bootstrapShipmentsOnADynamicChannel(
            SimpleMessageChannelBuilder::createQueueChannel('shipments_of_tenant'),
            PollingMetadata::create('shipments')->withCoroutines(3),
            SimpleMessageChannelBuilder::createStreamingChannel('audit'),
            DynamicMessageChannelBuilder::createThrottlingStrategy('shipments', 'shipment.isTenantServed', ['shipments_of_tenant']),
        );
        $ecotone->sendCommandWithRouting('shipment.ship');

        $ecotone->run('shipments', ExecutionPollingMetadata::createWithFinishWhenNoMessages()->withCoroutines(1));

        $this->assertSame(1, $ecotone->sendQueryWithRouting('shipment.shipped'));
    }

    public function test_consumer_of_a_dynamic_channel_with_a_custom_receiving_strategy_is_refused_when_the_application_has_a_stream(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(self::STREAM_CONSUMER_REFUSAL, 'the stream `audit`'));

        $this->bootstrapShipmentsOnADynamicChannel(
            SimpleMessageChannelBuilder::createQueueChannel('shipments_of_tenant'),
            PollingMetadata::create('shipments')->withCoroutines(3),
            SimpleMessageChannelBuilder::createStreamingChannel('audit'),
            DynamicMessageChannelBuilder::createRoundRobin('shipments', ['shipments_of_tenant'])->withCustomReceivingStrategy('shipment.channelToReceiveFrom'),
        );
    }

    public function test_consumer_of_a_stream_consumes_one_message_at_a_time(): void
    {
        $ecotone = $this->bootstrapShipments(channel: SimpleMessageChannelBuilder::createStreamingChannel('shipments'));
        $ecotone->sendCommandWithRouting('shipment.ship');

        $ecotone->run('shipments', ExecutionPollingMetadata::createWithFinishWhenNoMessages());

        $this->assertSame(1, $ecotone->sendQueryWithRouting('shipment.shipped'));
    }

    private function bootstrapReports(?PollingMetadata $pollingMetadata = null): FlowTestSupport
    {
        $reports = new class () {
            #[Scheduled(requestChannelName: 'report.send', endpointId: 'report.schedule')]
            public function schedule(): string
            {
                return 'daily';
            }

            #[CommandHandler('report.send')]
            public function send(string $report): void
            {
            }
        };

        return EcotoneLite::bootstrapFlowTesting([$reports::class], [$reports], extensionObjects: array_values(array_filter([$pollingMetadata])));
    }

    private function requireNoCoroutineRuntime(): void
    {
        if (extension_loaded('openswoole') || extension_loaded('swoole')) {
            $this->markTestSkipped('This PHP can run coroutines');
        }
    }

    private function bootstrapShipmentsOnADynamicChannel(SimpleMessageChannelBuilder $receivedFrom, ?PollingMetadata $pollingMetadata = null, ?MessageChannelBuilder $anotherChannel = null, ?DynamicMessageChannelBuilder $dynamicChannel = null): FlowTestSupport
    {
        $shipments = new class () {
            private int $shipped = 0;

            #[Asynchronous('shipments')]
            #[CommandHandler('shipment.ship', endpointId: 'shipment.ship.endpoint')]
            public function ship(): void
            {
                $this->shipped++;
            }

            #[QueryHandler('shipment.shipped')]
            public function shipped(): int
            {
                return $this->shipped;
            }

            #[InternalHandler('shipment.isTenantServed')]
            public function isTenantServed(): bool
            {
                return true;
            }

            #[InternalHandler('shipment.channelToReceiveFrom')]
            public function channelToReceiveFrom(): string
            {
                return 'shipments_of_tenant';
            }
        };

        return EcotoneLite::bootstrapFlowTesting(
            [$shipments::class],
            [$shipments],
            extensionObjects: array_values(array_filter([
                $dynamicChannel ?? DynamicMessageChannelBuilder::createRoundRobin('shipments', ['shipments_of_tenant']),
                $receivedFrom,
                $anotherChannel,
                $pollingMetadata,
            ])),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    private function bootstrapShipments(?PollingMetadata $pollingMetadata = null, ?SimpleMessageChannelBuilder $channel = null): FlowTestSupport
    {
        $shipments = new class () {
            private int $shipped = 0;

            #[Asynchronous('shipments')]
            #[CommandHandler('shipment.ship', endpointId: 'shipment.ship.endpoint')]
            public function ship(): void
            {
                $this->shipped++;
            }

            #[QueryHandler('shipment.shipped')]
            public function shipped(): int
            {
                return $this->shipped;
            }
        };

        return EcotoneLite::bootstrapFlowTesting(
            [$shipments::class],
            [$shipments],
            extensionObjects: array_values(array_filter([$channel ?? SimpleMessageChannelBuilder::createQueueChannel('shipments'), $pollingMetadata])),
        );
    }
}
