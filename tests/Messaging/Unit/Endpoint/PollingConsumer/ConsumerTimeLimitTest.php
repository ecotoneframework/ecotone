<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint\PollingConsumer;

use DateTimeImmutable;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

/**
 * licence Apache-2.0
 * @internal
 */
final class ConsumerTimeLimitTest extends TestCase
{
    public function test_consumer_polls_once_when_its_time_limit_runs_out_before_the_first_poll(): void
    {
        $ecotone = $this->bootstrapWithClockAdvancingTenMillisecondsOnEveryReading();

        $ecotone->sendCommandWithRouting('register', 'first');
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 10, executionTimeLimitInMilliseconds: 1));

        $this->assertSame(['first'], $ecotone->sendQueryWithRouting('registered'));
    }

    public function test_consumer_stops_after_the_first_poll_when_its_time_limit_has_run_out(): void
    {
        $ecotone = $this->bootstrapWithClockAdvancingTenMillisecondsOnEveryReading();

        $ecotone->sendCommandWithRouting('register', 'first');
        $ecotone->sendCommandWithRouting('register', 'second');
        $ecotone->sendCommandWithRouting('register', 'third');
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 10, executionTimeLimitInMilliseconds: 1));

        $this->assertSame(['first'], $ecotone->sendQueryWithRouting('registered'));
    }

    private function bootstrapWithClockAdvancingTenMillisecondsOnEveryReading(): FlowTestSupport
    {
        $registry = new class () {
            private array $registered = [];

            #[Asynchronous('async')]
            #[CommandHandler('register', endpointId: 'register.endpoint')]
            public function register(string $name): void
            {
                $this->registered[] = $name;
            }

            #[QueryHandler('registered')]
            public function registered(): array
            {
                return $this->registered;
            }
        };
        $clock = new class () implements ClockInterface {
            private DateTimeImmutable $now;

            public function __construct()
            {
                $this->now = new DateTimeImmutable('2026-01-01 00:00:00');
            }

            public function now(): DateTimeImmutable
            {
                $this->now = $this->now->modify('+10 milliseconds');

                return $this->now;
            }
        };

        return EcotoneLite::bootstrapFlowTesting(
            [$registry::class],
            [$registry, ClockInterface::class => $clock],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
            enabledModules: [],
        );
    }
}
