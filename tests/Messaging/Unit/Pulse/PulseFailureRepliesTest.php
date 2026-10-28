<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Pulse;

use DateTimeImmutable;
use DateTimeZone;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Pulse\PulseConfiguration;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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
final class PulseFailureRepliesTest extends TestCase
{
    public function test_a_failing_pulse_request_is_answered_with_a_failure_reply_and_is_neither_retried_nor_sent_to_the_error_channel(): void
    {
        $orders = $this->orders();
        $pulse = $this->pulseReplies();
        $channels = $this->channels();
        $orderService = $this->service($orders, [...$channels, ...$this->errorChannel(), $this->pulseMap(), PulseConfiguration::createWithDefaults('ecotone_pulse')], 'order_service');
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.custom_action', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $attemptsOfFirstRun = $orders->attempts;
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup());
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertSame($attemptsOfFirstRun, $orders->attempts);
        self::assertSame([], $orders->failed);
        self::assertCount(1, $pulse->replies);
        $reply = json_decode($pulse->replies[0], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('ecotone.pulse.custom_action', $reply['requestKind']);
        self::assertSame('order_service', $reply['serviceName']);
        self::assertNull($reply['tenant']);
        self::assertSame('Custom action cannot be done', $reply['message']);
        self::assertSame('2026-10-20T12:00:00+00:00', $reply['failedAt']);
    }

    public function test_a_failing_message_that_is_not_a_pulse_request_goes_to_the_error_channel_and_is_not_answered(): void
    {
        $orders = $this->orders();
        $pulse = $this->pulseReplies();
        $channels = $this->channels();
        $orderService = $this->service($orders, [...$channels, ...$this->errorChannel(), $this->pulseMap(), PulseConfiguration::createWithDefaults('ecotone_pulse')], 'order_service');
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'order.place', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false));
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertCount(1, $orders->failed);
        self::assertSame([], $pulse->replies);
    }

    public function test_a_pulse_routing_key_from_another_service_goes_to_the_error_channel_and_is_not_answered(): void
    {
        $orders = $this->orders();
        $pulse = $this->pulseReplies();
        $channels = $this->channels();
        $orderService = $this->service($orders, [...$channels, ...$this->errorChannel(), $this->pulseMap(), PulseConfiguration::createWithDefaults('ecotone_pulse')], 'order_service');
        $intruder = $this->service($this->pulseReplies(), [...$channels, DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'order_service', channelName: 'order_channel')], 'intruder');
        $pulseService = $this->pulseService($pulse, $channels);

        $intruder->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.custom_action', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false));
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertCount(1, $orders->failed);
        self::assertSame([], $pulse->replies);
    }

    public function test_a_service_without_pulse_configuration_sends_every_failure_to_the_error_channel(): void
    {
        $orders = $this->orders();
        $pulse = $this->pulseReplies();
        $channels = $this->channels();
        $orderService = $this->service($orders, [...$channels, ...$this->errorChannel(), $this->pulseMap()], 'order_service');
        $pulseService = $this->pulseService($pulse, $channels);

        $pulseService->getDistributedBus()->sendMessage('order_service', 'ecotone.pulse.custom_action', '{}', MediaType::APPLICATION_JSON, replyRoutingKey: 'ecotone.pulse.snapshot_received');
        $orderService->run('order_channel', ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false));
        $pulseService->run('pulse_channel', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertCount(1, $orders->failed);
        self::assertSame([], $pulse->replies);
    }

    private function orders(): object
    {
        return new class () {
            public int $attempts = 0;
            public array $failed = [];

            #[Distributed]
            #[CommandHandler('ecotone.pulse.custom_action')]
            public function customAction(string $payload): void
            {
                $this->attempts++;
                throw new RuntimeException('Custom action cannot be done');
            }

            #[Distributed]
            #[CommandHandler('order.place')]
            public function place(string $payload): void
            {
                throw new RuntimeException('Order cannot be placed');
            }

            #[InternalHandler('error_channel')]
            public function failedMessage(mixed $payload): void
            {
                $this->failed[] = $payload;
            }
        };
    }

    private function pulseReplies(): object
    {
        return new class () {
            public array $replies = [];

            #[InternalHandler('ecotone.pulse.snapshot_received')]
            public function received(string $reply): void
            {
                $this->replies[] = $reply;
            }
        };
    }

    private function channels(): array
    {
        return [SimpleMessageChannelBuilder::createQueueChannel('order_channel'), SimpleMessageChannelBuilder::createQueueChannel('pulse_channel')];
    }

    private function errorChannel(): array
    {
        return [SimpleMessageChannelBuilder::createPublishSubscribeChannel('error_channel'), PollingMetadata::create('order_channel')->withErrorChannelName('error_channel')];
    }

    private function pulseMap(): DistributedServiceMap
    {
        return DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'ecotone_pulse', channelName: 'pulse_channel');
    }

    private function service(object $handlers, array $extensionObjects, string $serviceName): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$handlers::class],
            containerOrAvailableServices: [$handlers],
            extensionObjects: [...$extensionObjects, ServiceConfiguration::createWithDefaults()->withServiceName($serviceName)],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2026-10-20 12:00:00', new DateTimeZone('UTC'))),
        );
    }

    private function pulseService(object $handlers, array $channels): FlowTestSupport
    {
        return $this->service($handlers, [...$channels, DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'order_service', channelName: 'order_channel')], 'ecotone_pulse');
    }
}
