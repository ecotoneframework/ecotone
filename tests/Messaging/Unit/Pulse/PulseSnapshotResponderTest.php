<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Pulse;

use DateTimeImmutable;
use DateTimeZone;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Pulse\PulseConfiguration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
final class PulseSnapshotResponderTest extends TestCase
{
    public function test_the_snapshot_reply_describes_the_service_in_one_json_document(): void
    {
        $orders = $this->orders();
        $pulse = $this->pulseReplies();
        $channels = $this->channels();
        $orderService = $this->orderService($orders, $channels);
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertCount(1, $pulse->snapshots);
        $snapshot = json_decode($pulse->snapshots[0], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(1, $snapshot['schemaVersion']);
        self::assertSame('order_service', $snapshot['serviceName']);
        self::assertSame('2026-10-20T12:00:00+00:00', $snapshot['takenAt']);
        self::assertSame(PHP_VERSION, $snapshot['overview']['phpVersion']);
        self::assertSame('lite', $snapshot['overview']['framework']);
        self::assertSame(['enterprise' => true, 'expiresAt' => null], $snapshot['overview']['licence']);
        self::assertIsString($snapshot['overview']['ecotoneVersion']);
        self::assertNotSame('', $snapshot['overview']['ecotoneVersion']);
        self::assertArrayHasKey('nodes', $snapshot['flowGraph']);
        self::assertArrayHasKey('edges', $snapshot['flowGraph']);
        self::assertContains('order.place', array_column($snapshot['flowGraph']['nodes'], 'label'));
        self::assertSame(
            [[
                'referenceName' => 'Ecotone\Api\Gateway\DistributedBus',
                'commandMapping' => ['ecotone_pulse' => 'pulse_channel'],
                'eventSubscriptions' => [],
                'asynchronousChannel' => null,
            ]],
            $snapshot['topology']['serviceMaps'],
        );
    }

    public function test_the_snapshot_lists_the_asynchronous_channels_a_dead_letter_can_be_replayed_to_with_their_kind(): void
    {
        $handlers = new class () {
            #[Asynchronous('orders_async')]
            #[CommandHandler('order.place_later', endpointId: 'order.place_later.endpoint')]
            public function placeLater(string $orderId): void
            {
            }

            #[CommandHandler('order.place')]
            public function place(string $orderId): void
            {
            }

            #[CommandHandler]
            public function placeByClass(PulseClassRoutedOrder $order): void
            {
            }

            #[InternalHandler('order.audit')]
            public function audit(string $orderId): void
            {
            }

            #[QueryHandler('order.status')]
            public function status(string $orderId): string
            {
                return $orderId;
            }
        };
        $channels = [...$this->channels(), SimpleMessageChannelBuilder::createQueueChannel('orders_async'), SimpleMessageChannelBuilder::createQueueChannel('unused_queue')];
        $orderService = $this->orderService($handlers, $channels);
        $pulse = $this->pulseReplies();
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        $snapshot = json_decode($pulse->snapshots[0], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(1, $snapshot['schemaVersion']);
        self::assertSame([
            ['name' => 'order_channel', 'kind' => 'queue', 'consumerToRun' => 'order_channel', 'handlers' => [], 'ready' => null, 'delayed' => null, 'inFlight' => null, 'consumers' => null, 'probeError' => null, 'setUp' => null],
            ['name' => 'orders_async', 'kind' => 'async', 'consumerToRun' => 'orders_async', 'handlers' => ['order.place_later → placeLater'], 'ready' => null, 'delayed' => null, 'inFlight' => null, 'consumers' => null, 'probeError' => null, 'setUp' => null],
            ['name' => 'unused_queue', 'kind' => 'queue', 'consumerToRun' => 'unused_queue', 'handlers' => [], 'ready' => null, 'delayed' => null, 'inFlight' => null, 'consumers' => null, 'probeError' => null, 'setUp' => null],
        ], $snapshot['channels']);
    }

    public function test_an_asynchronous_service_map_is_reported_in_the_topology(): void
    {
        $channels = $this->channels();
        $orderService = $this->orderService($this->orders(), $channels, DistributedServiceMap::initialize()
            ->withCommandMapping(targetServiceName: 'ecotone_pulse', channelName: 'pulse_channel')
            ->withEventMapping(channelName: 'order_channel', subscriptionKeys: ['order.*'], excludePublishingServices: ['legacy'])
            ->withEventMapping(channelName: 'audit_channel', subscriptionKeys: ['audit.*'], includePublishingServices: ['billing'])
            ->withAsynchronousChannel('order_channel'));
        $pulse = $this->pulseReplies();
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        $snapshot = json_decode($pulse->snapshots[0], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(
            [
                ['channel' => 'order_channel', 'subscriptionKeys' => ['order.*'], 'excludePublishingServices' => ['legacy'], 'includePublishingServices' => []],
                ['channel' => 'audit_channel', 'subscriptionKeys' => ['audit.*'], 'excludePublishingServices' => [], 'includePublishingServices' => ['billing']],
            ],
            $snapshot['topology']['serviceMaps'][0]['eventSubscriptions'],
        );
        self::assertSame('order_channel', $snapshot['topology']['serviceMaps'][0]['asynchronousChannel']);
    }

    public function test_a_request_from_a_service_other_than_the_pulse_service_is_refused(): void
    {
        $channels = [...$this->channels(), SimpleMessageChannelBuilder::createQueueChannel('intruder_channel')];
        $orderService = $this->orderService($this->orders(), $channels);
        $intruder = EcotoneLite::bootstrapFlowTesting(
            extensionObjects: [
                ...$channels,
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'order_service', channelName: 'order_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('intruder'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $intruder->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Service order_service was asked for its Pulse snapshot by intruder, but it only answers the configured Pulse service ecotone_pulse. Send the request from the Pulse service, or change the accepted service with PulseConfiguration::createWithDefaults(pulseServiceName: 'intruder') in a #[ServiceContext].");
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
    }

    public function test_the_reply_does_not_wait_for_the_responders_asynchronous_distributed_bus(): void
    {
        $channels = [...$this->channels(), SimpleMessageChannelBuilder::createQueueChannel('order_bus')];
        $orderService = $this->orderService($this->orders(), $channels, $this->pulseMap()->withAsynchronousChannel('order_bus'));
        $pulse = $this->pulseReplies();
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertCount(1, $pulse->snapshots);
        $orderService->run('order_bus', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());
        self::assertCount(1, $pulse->snapshots);
        self::assertSame('order_bus', json_decode($pulse->snapshots[0], true, flags: JSON_THROW_ON_ERROR)['topology']['serviceMaps'][0]['asynchronousChannel']);
    }

    public function test_a_request_without_a_source_service_is_refused(): void
    {
        $channels = $this->channels();
        $orders = $this->orders();
        $orderService = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$orders::class, SourcelessPulseRequest::class],
            containerOrAvailableServices: [$orders],
            extensionObjects: [
                ...$channels,
                $this->pulseMap(),
                ServiceConfiguration::createWithDefaults()->withServiceName('order_service'),
                PulseConfiguration::createWithDefaults('ecotone_pulse'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('was asked for its Pulse snapshot by no service');
        $orderService->getGateway(SourcelessPulseRequest::class)->ask('{}');
    }

    public function test_pulse_without_an_enterprise_licence_is_refused_at_bootstrap(): void
    {
        $this->expectException(LicensingException::class);
        $this->expectExceptionMessage('Ecotone Pulse is available only as part of Ecotone Enterprise. Provide a licence key, or remove the PulseConfiguration from your #[ServiceContext]. See https://docs.ecotone.tech/enterprise for details.');

        EcotoneLite::bootstrapFlowTesting(
            extensionObjects: [...$this->channels(), PulseConfiguration::createWithDefaults('ecotone_pulse'), ServiceConfiguration::createWithDefaults()->withServiceName('order_service')],
            enabledModules: [],
        );
    }

    public function test_pulse_without_a_service_name_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("PulseConfiguration needs this service to be named, so Pulse can tell which service answered. Set it with ServiceConfiguration::createWithDefaults()->withServiceName('my_service') in a #[ServiceContext].");

        EcotoneLite::bootstrapFlowTesting(
            extensionObjects: [...$this->channels(), PulseConfiguration::createWithDefaults('ecotone_pulse')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_the_reply_reaches_pulse_without_a_service_map_when_the_pulse_channel_is_registered(): void
    {
        $pulse = $this->pulseReplies();
        $channels = [...$this->channels(), SimpleMessageChannelBuilder::createQueueChannel('ecotone_pulse')];
        $orderService = $this->orderServiceWithoutMap($this->orders(), $channels, PulseConfiguration::createWithDefaults('ecotone_pulse'));
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('ecotone_pulse', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertCount(1, $pulse->snapshots);
        self::assertSame('order_service', json_decode($pulse->snapshots[0], true, flags: JSON_THROW_ON_ERROR)['serviceName']);
    }

    public function test_the_reply_goes_to_the_channel_chosen_with_with_reply_channel(): void
    {
        $pulse = $this->pulseReplies();
        $channels = [...$this->channels(), SimpleMessageChannelBuilder::createQueueChannel('pulse_replies')];
        $orderService = $this->orderServiceWithoutMap($this->orders(), $channels, PulseConfiguration::createWithDefaults('ecotone_pulse')->withReplyChannel('pulse_replies'));
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_replies', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertCount(1, $pulse->snapshots);
    }

    public function test_an_explicit_service_map_agreeing_with_the_reply_channel_is_accepted(): void
    {
        $pulse = $this->pulseReplies();
        $channels = $this->channels();
        $orderService = $this->orderServiceWithoutMap($this->orders(), $channels, PulseConfiguration::createWithDefaults('ecotone_pulse')->withReplyChannel('pulse_channel'), $this->pulseMap());
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertCount(1, $pulse->snapshots);
    }

    public function test_an_explicit_service_map_wins_when_no_reply_channel_was_chosen(): void
    {
        $pulse = $this->pulseReplies();
        $channels = $this->channels();
        $orderService = $this->orderServiceWithoutMap($this->orders(), $channels, PulseConfiguration::createWithDefaults('ecotone_pulse'), $this->pulseMap());
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertCount(1, $pulse->snapshots);
    }

    public function test_an_explicit_service_map_contradicting_the_reply_channel_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("PulseConfiguration sends the reply to channel 'pulse_replies' (withReplyChannel), but a DistributedServiceMap maps Pulse service ecotone_pulse to channel 'pulse_channel', so the reply could go to only one of them. Make both name the same channel: change withReplyChannel('pulse_replies') to withReplyChannel('pulse_channel'), or remove withReplyChannel to follow the DistributedServiceMap, or remove the command mapping of 'ecotone_pulse' from the DistributedServiceMap.");

        $this->orderServiceWithoutMap($this->orders(), [...$this->channels(), SimpleMessageChannelBuilder::createQueueChannel('pulse_replies')], PulseConfiguration::createWithDefaults('ecotone_pulse')->withReplyChannel('pulse_replies'), $this->pulseMap());
    }

    public function test_a_reply_channel_that_cannot_be_derived_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Pulse replies go to channel 'ecotone_pulse', which is not a registered message channel, and it cannot follow the request channel's transport because the request channel 'order_service' is not registered either. Register the channel Pulse sends its requests to, named after the service ('order_service') or chosen with PulseConfiguration::withRequestChannel('...'). Or register 'ecotone_pulse' explicitly, e.g. AmqpBackedMessageChannelBuilder::create('ecotone_pulse') (or the DbalBackedMessageChannelBuilder, RedisBackedMessageChannelBuilder, SqsBackedMessageChannelBuilder or KafkaMessageChannelBuilder::create('ecotone_pulse') your other channels use), or send the replies to another registered channel with PulseConfiguration::withReplyChannel().");

        $this->orderServiceWithoutMap($this->orders(), $this->channels(), PulseConfiguration::createWithDefaults('ecotone_pulse'));
    }

    public function test_a_request_channel_whose_builder_cannot_be_recreated_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Pulse replies go to channel 'ecotone_pulse', which is not a registered message channel, and the builder of request channel 'order_channel' cannot be re-created for another channel name. Register 'ecotone_pulse' explicitly, e.g. AmqpBackedMessageChannelBuilder::create('ecotone_pulse') (or the DbalBackedMessageChannelBuilder, RedisBackedMessageChannelBuilder, SqsBackedMessageChannelBuilder or KafkaMessageChannelBuilder::create('ecotone_pulse') your other channels use), or send the replies to another registered channel with PulseConfiguration::withReplyChannel().");

        $this->orderServiceWithoutMap($this->orders(), $this->channels(), PulseConfiguration::createWithDefaults('ecotone_pulse')->withRequestChannel('order_channel'));
    }

    public function test_a_missing_reply_channel_is_refused_even_when_the_service_maps_other_services(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Pulse replies go to channel 'ecotone_pulse', which is not a registered message channel");

        $this->orderServiceWithoutMap(
            $this->orders(),
            [...$this->channels(), SimpleMessageChannelBuilder::createQueueChannel('billing_channel')],
            PulseConfiguration::createWithDefaults('ecotone_pulse'),
            DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'billing', channelName: 'billing_channel'),
        );
    }

    public function test_a_service_mapping_other_services_still_routes_to_them_and_replies_to_pulse_through_the_implied_mapping(): void
    {
        $pulse = $this->pulseReplies();
        $billing = new class () {
            public array $received = [];

            #[Distributed]
            #[CommandHandler('billing.charge')]
            public function charge(string $orderId): void
            {
                $this->received[] = $orderId;
            }
        };
        $channels = [...$this->channels(), SimpleMessageChannelBuilder::createQueueChannel('ecotone_pulse'), SimpleMessageChannelBuilder::createQueueChannel('billing_channel')];
        $orderService = $this->orderServiceWithoutMap(
            $this->orders(),
            $channels,
            PulseConfiguration::createWithDefaults('ecotone_pulse'),
            DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'billing', channelName: 'billing_channel'),
        );
        $pulseService = $this->pulseService($pulse, $channels);
        $billingService = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$billing::class],
            containerOrAvailableServices: [$billing],
            extensionObjects: [...$channels, ServiceConfiguration::createWithDefaults()->withServiceName('billing')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $orderService->getDistributedBus()->sendCommand('billing', 'billing.charge', 'order-1');
        $billingService->run('billing_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.snapshot', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('ecotone_pulse', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertSame(['order-1'], $billing->received);
        self::assertCount(1, $pulse->snapshots);
        $snapshot = json_decode($pulse->snapshots[0], true, flags: JSON_THROW_ON_ERROR);
        $mappedServices = array_merge(...array_map(static fn (array $map) => array_keys($map['commandMapping']), $snapshot['topology']['serviceMaps']));
        sort($mappedServices);
        self::assertSame(['billing', 'ecotone_pulse'], $mappedServices);
    }

    public function test_an_explicit_mapping_to_a_channel_that_is_not_registered_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('not available in message channels');

        $this->orderServiceWithoutMap($this->orders(), [SimpleMessageChannelBuilder::createQueueChannel('order_channel')], PulseConfiguration::createWithDefaults('ecotone_pulse'), $this->pulseMap());
    }

    public function test_a_reply_channel_equal_to_the_request_channel_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Pulse replies would go to channel 'order_channel', which is also the channel Pulse sends its requests to, so the service would consume its own replies. Choose another reply channel with PulseConfiguration::withReplyChannel(), or another request channel with PulseConfiguration::withRequestChannel().");

        $this->orderServiceWithoutMap($this->orders(), $this->channels(), PulseConfiguration::createWithDefaults('ecotone_pulse')->withRequestChannel('order_channel')->withReplyChannel('order_channel'));
    }

    public function test_pulse_configured_twice_is_refused_at_bootstrap(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('PulseConfiguration was registered 2 times, but a service answers to exactly one Pulse service. Keep a single #[ServiceContext] returning PulseConfiguration::createWithDefaults(pulseServiceName: ...) and remove the others.');

        EcotoneLite::bootstrapFlowTesting(
            extensionObjects: [
                ...$this->channels(),
                PulseConfiguration::createWithDefaults('ecotone_pulse'),
                PulseConfiguration::createWithDefaults('another_pulse'),
                ServiceConfiguration::createWithDefaults()->withServiceName('order_service'),
                $this->pulseMap(),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    private function pulseMap(): DistributedServiceMap
    {
        return DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'ecotone_pulse', channelName: 'pulse_channel');
    }

    private function orders(): object
    {
        return new class () {
            #[CommandHandler('order.place')]
            public function place(string $orderId): void
            {
            }
        };
    }

    private function pulseReplies(): object
    {
        return new class () {
            public array $snapshots = [];

            #[InternalHandler('ecotone.pulse.snapshot_received')]
            public function received(string $snapshot): void
            {
                $this->snapshots[] = $snapshot;
            }
        };
    }

    private function channels(): array
    {
        return [SimpleMessageChannelBuilder::createQueueChannel('order_channel'), SimpleMessageChannelBuilder::createQueueChannel('pulse_channel')];
    }

    private function orderService(object $handlers, array $channels, ?DistributedServiceMap $serviceMap = null): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handlers::class],
            containerOrAvailableServices: [$handlers],
            extensionObjects: [
                ...$channels,
                $serviceMap ?? $this->pulseMap(),
                ServiceConfiguration::createWithDefaults()->withServiceName('order_service'),
                PulseConfiguration::createWithDefaults('ecotone_pulse'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2026-10-20 12:00:00', new DateTimeZone('UTC'))),
        );
    }

    private function orderServiceWithoutMap(object $handlers, array $channels, PulseConfiguration $pulseConfiguration, ?DistributedServiceMap $serviceMap = null): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handlers::class],
            containerOrAvailableServices: [$handlers],
            extensionObjects: [
                ...$channels,
                ...($serviceMap === null ? [] : [$serviceMap]),
                ServiceConfiguration::createWithDefaults()->withServiceName('order_service'),
                $pulseConfiguration,
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2026-10-20 12:00:00', new DateTimeZone('UTC'))),
        );
    }

    private function pulseService(object $handlers, array $channels): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handlers::class],
            containerOrAvailableServices: [$handlers],
            extensionObjects: [
                ...$channels,
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'order_service', channelName: 'order_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('ecotone_pulse'),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }
}

interface SourcelessPulseRequest
{
    #[MessageGateway('ecotone.pulse.snapshot')]
    public function ask(#[Payload] string $payload): string;
}

final class PulseClassRoutedOrder
{
}
