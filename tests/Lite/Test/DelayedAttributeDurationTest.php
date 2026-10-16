<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use DateTimeImmutable;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Delayed;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class DelayedAttributeDurationTest extends TestCase
{
    public function test_delay_can_be_declared_with_named_durations(): void
    {
        $handler = new class () {
            public array $expired = [];

            #[Asynchronous('async')]
            #[Delayed(hours: 1, minutes: 30)]
            #[EventHandler('basket.changed', endpointId: 'expireBasket')]
            public function expire(string $basketId): void
            {
                $this->expired[] = $basketId;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            enabledModules: [],
        );
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 12:00:00'));

        $ecotone->publishEventWithRouting('basket.changed', 'basket-1');
        $ecotone->advanceTimeBy(TimeSpan::withMinutes(89))->run('async');
        $this->assertSame([], $handler->expired);

        $ecotone->advanceTimeBy(TimeSpan::withMinutes(1))->run('async');
        $this->assertSame(['basket-1'], $handler->expired);
    }

    public function test_delay_given_both_as_time_and_named_durations_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('#[Delayed] takes either $time or named durations (milliseconds, seconds, minutes, hours, days), not both.');

        new Delayed(new TimeSpan(hours: 1), hours: 2);
    }
}
