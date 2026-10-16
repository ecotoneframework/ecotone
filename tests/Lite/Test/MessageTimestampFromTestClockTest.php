<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use DateTimeImmutable;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Scheduled;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class MessageTimestampFromTestClockTest extends TestCase
{
    public function test_scheduled_method_result_is_stamped_with_the_test_clock(): void
    {
        $service = new class () {
            public ?int $timestamp = null;

            #[Scheduled('handle', 'tick')]
            public function tick(): string
            {
                return 'tick';
            }

            #[InternalHandler('handle')]
            public function handle(string $payload, #[Header(MessageHeaders::TIMESTAMP)] int $timestamp): void
            {
                $this->timestamp = $timestamp;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting([$service::class], [$service]);
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 13:00:00'));

        $ecotone->run('tick', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, executionTimeLimitInMilliseconds: 0));

        $this->assertSame((new DateTimeImmutable('2026-03-01 13:00:00'))->getTimestamp(), $service->timestamp);
    }

    public function test_message_sent_direct_to_channel_is_stamped_with_the_test_clock(): void
    {
        $service = new class () {
            public ?int $timestamp = null;

            #[InternalHandler('handle')]
            public function handle(string $payload, #[Header(MessageHeaders::TIMESTAMP)] int $timestamp): void
            {
                $this->timestamp = $timestamp;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting([$service::class], [$service]);
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 13:00:00'));

        $ecotone->sendDirectToChannel('handle', 'payload');

        $this->assertSame((new DateTimeImmutable('2026-03-01 13:00:00'))->getTimestamp(), $service->timestamp);
    }
}
