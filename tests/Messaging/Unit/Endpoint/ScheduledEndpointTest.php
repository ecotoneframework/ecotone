<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint;

use DateTimeImmutable;
use Ecotone\Api\Attribute\Scheduled;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Scheduling\Duration;
use Ecotone\Api\Scheduling\SleepInterface;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ChannelTestSupport;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ScheduledEndpointTest extends TestCase
{
    public function test_scheduled_method_with_no_parameters_is_called_with_no_request_channel_needed(): void
    {
        $service = new NoArgScheduledService();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [NoArgScheduledService::class],
            [$service],
            enabledModules: [],
        );

        $ecotone->run(NoArgScheduledService::ENDPOINT_ID, ExecutionPollingMetadata::createWithTestingSetup(1));

        $this->assertTrue($service->wasCalled);
    }

    public function test_scheduled_method_return_value_is_dispatched_to_the_declared_request_channel(): void
    {
        $service = new ReturningScheduledService();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ReturningScheduledService::class],
            [$service],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel(ReturningScheduledService::CHANNEL)],
            enabledModules: [],
        );

        $ecotone->run(ReturningScheduledService::ENDPOINT_ID, ExecutionPollingMetadata::createWithTestingSetup(1));

        $this->assertSame(ReturningScheduledService::PAYLOAD, ChannelTestSupport::of($ecotone)->receiveMessageFrom(ReturningScheduledService::CHANNEL)->getPayload());
    }

    public function test_a_custom_fixed_rate_and_execution_time_limit_can_be_registered_for_a_scheduled_endpoint(): void
    {
        $service = new ReturningScheduledService();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ReturningScheduledService::class],
            [$service],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel(ReturningScheduledService::CHANNEL),
                PollingMetadata::create(ReturningScheduledService::ENDPOINT_ID)->withFixedRateInMilliseconds(1)->withInitialDelayInMilliseconds(0)->withExecutionTimeLimitInMilliseconds(100),
            ],
            enabledModules: [],
        );

        $ecotone->run(ReturningScheduledService::ENDPOINT_ID);

        $this->assertSame(ReturningScheduledService::PAYLOAD, ChannelTestSupport::of($ecotone)->receiveMessageFrom(ReturningScheduledService::CHANNEL)->getPayload());
    }

    public function test_a_scheduled_endpoint_runs_on_its_first_poll_without_waiting(): void
    {
        [$ecotone, $clock, $service] = $this->bootstrapCountingScheduledEndpointWithFixedRate(1000);

        $ecotone->run('countingScheduler', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, executionTimeLimitInMilliseconds: 0));

        $this->assertSame(1, $service->calls);
        $this->assertSame([], $clock->sleptMicroseconds);
    }

    public function test_a_scheduled_endpoint_waits_its_fixed_rate_before_the_next_run(): void
    {
        [$ecotone, $clock, $service] = $this->bootstrapCountingScheduledEndpointWithFixedRate(1000);

        $ecotone->run('countingScheduler', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 2, executionTimeLimitInMilliseconds: 0));

        $this->assertSame(2, $service->calls);
        $this->assertSame([1_000_000], $clock->sleptMicroseconds);
    }

    public function test_bootstrap_throws_when_scheduled_method_declares_a_parameter(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [UnresolvableParameterScheduledService::class],
            [new UnresolvableParameterScheduledService()],
            enabledModules: [],
        );
    }

    /**
     * @return array{0: FlowTestSupport, 1: object, 2: object}
     */
    private function bootstrapCountingScheduledEndpointWithFixedRate(int $fixedRateInMilliseconds): array
    {
        $service = new class () {
            public int $calls = 0;

            #[Scheduled('countedTicks', 'countingScheduler')]
            public function tick(): string
            {
                $this->calls++;

                return 'tick';
            }
        };
        $clock = new class () implements ClockInterface, SleepInterface {
            public array $sleptMicroseconds = [];
            private DateTimeImmutable $now;

            public function __construct()
            {
                $this->now = new DateTimeImmutable('2016-01-01 12:00:00');
            }

            public function now(): DateTimeImmutable
            {
                return $this->now;
            }

            public function sleep(Duration $duration): void
            {
                if ($duration->isNegativeOrZero()) {
                    return;
                }
                $this->sleptMicroseconds[] = $duration->inMicroseconds();
                $this->now = $this->now->modify("+{$duration->inMicroseconds()} microseconds");
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service, ClockInterface::class => $clock],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('countedTicks'),
                PollingMetadata::create('countingScheduler')->withFixedRateInMilliseconds($fixedRateInMilliseconds),
            ],
            enabledModules: [],
        );

        return [$ecotone, $clock, $service];
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class NoArgScheduledService
{
    public const ENDPOINT_ID = 'noArgSchedule';

    public bool $wasCalled = false;

    #[Scheduled(endpointId: self::ENDPOINT_ID)]
    public function execute(): void
    {
        $this->wasCalled = true;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ReturningScheduledService
{
    public const ENDPOINT_ID = 'returningSchedule';
    public const CHANNEL = 'returningSchedule.channel';
    public const PAYLOAD = 'testPayload';

    #[Scheduled(self::CHANNEL, self::ENDPOINT_ID)]
    public function execute(): string
    {
        return self::PAYLOAD;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class UnresolvableParameterScheduledService
{
    #[Scheduled]
    public function execute(UnresolvableParameterScheduledServiceArgument $argument): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class UnresolvableParameterScheduledServiceArgument
{
}
