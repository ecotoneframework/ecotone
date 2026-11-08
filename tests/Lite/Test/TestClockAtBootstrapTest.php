<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use DateTimeImmutable;
use DateTimeZone;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Scheduling\DatePoint;
use Ecotone\Api\Scheduling\Duration;
use Ecotone\Messaging\Config\ConfigurationException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class TestClockAtBootstrapTest extends TestCase
{
    public function test_the_configured_start_time_stamps_the_first_message_the_test_sends(): void
    {
        $service = new class () {
            public ?int $timestamp = null;

            #[CommandHandler('order.place')]
            public function place(string $orderId, #[Header(MessageHeaders::TIMESTAMP)] int $timestamp): void
            {
                $this->timestamp = $timestamp;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service],
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2024-03-01 10:00:00')),
        );

        $ecotone->sendCommandWithRouting('order.place', 'order-1');

        self::assertSame((new DateTimeImmutable('2024-03-01 10:00:00'))->getTimestamp(), $service->timestamp);
        self::assertEquals(new DatePoint('2024-03-01 10:00:00'), $ecotone->now());
    }

    public function test_now_follows_the_time_the_test_moves_to(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2024-03-01 10:00:00')),
        );

        $ecotone->advanceTimeBy(Duration::minutes(60));

        self::assertEquals(new DatePoint('2024-03-01 11:00:00'), $ecotone->now());
    }

    public function test_now_runs_on_real_time_until_the_test_pins_it(): void
    {
        $before = new DateTimeImmutable();
        $ecotone = EcotoneLite::bootstrapFlowTesting();

        self::assertGreaterThanOrEqual($before, $ecotone->now());

        $ecotone->changeTimeTo(new DateTimeImmutable('2030-01-01 00:00:00'));

        self::assertEquals(new DatePoint('2030-01-01 00:00:00'), $ecotone->now());
        self::assertEquals($ecotone->now(), $ecotone->now());
    }

    public function test_moving_time_before_the_configured_start_is_refused(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2024-03-01 10:00:00')),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move time backwards: the test clock is at 2024-03-01 10:00:00.000000 and you requested 2024-02-29 10:00:00.000000. Request a later time, or use advanceTimeBy() to move forward relative to the current time.');

        $ecotone->changeTimeTo(new DateTimeImmutable('2024-02-29 10:00:00'));
    }

    public function test_the_initial_time_stamps_the_first_message_the_test_sends(): void
    {
        $service = new class () {
            public ?int $timestamp = null;

            #[CommandHandler('order.place')]
            public function place(string $orderId, #[Header(MessageHeaders::TIMESTAMP)] int $timestamp): void
            {
                $this->timestamp = $timestamp;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service],
            initialTime: new DateTimeImmutable('2024-03-01 10:00:00'),
        );

        $ecotone->sendCommandWithRouting('order.place', 'order-1');

        self::assertSame((new DateTimeImmutable('2024-03-01 10:00:00'))->getTimestamp(), $service->timestamp);
        self::assertEquals(new DatePoint('2024-03-01 10:00:00'), $ecotone->now());
    }

    public function test_now_follows_the_time_the_test_moves_to_from_the_initial_time(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            initialTime: new DateTimeImmutable('2024-03-01 10:00:00'),
        );

        $ecotone->advanceTimeBy(Duration::minutes(60));

        self::assertEquals(new DatePoint('2024-03-01 11:00:00'), $ecotone->now());
    }

    public function test_the_initial_time_keeps_the_other_settings_of_the_given_test_configuration(): void
    {
        $service = new class () {
            #[Asynchronous('async')]
            #[CommandHandler('order.place', endpointId: 'order.place.endpoint')]
            public function place(string $orderId): void
            {
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async'),
            initialTime: new DateTimeImmutable('2024-03-01 10:00:00'),
        );

        $ecotone->sendCommandWithRouting('order.place', 'order-1');

        $recorded = $ecotone->popRecordedMessagesFrom('async');
        self::assertCount(1, $recorded);
        self::assertSame((new DateTimeImmutable('2024-03-01 10:00:00'))->getTimestamp(), $recorded[0]->getHeaders()->getTimestamp());
    }

    public function test_the_initial_time_and_a_test_configuration_pinned_to_the_same_instant_are_accepted(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2024-03-01 10:00:00', new DateTimeZone('UTC'))),
            initialTime: new DateTimeImmutable('2024-03-01 12:00:00', new DateTimeZone('+02:00')),
        );

        self::assertEquals(new DatePoint('2024-03-01 10:00:00', new DateTimeZone('UTC')), $ecotone->now());
    }

    public function test_the_initial_time_and_a_test_configuration_pinned_to_another_instant_are_refused(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('EcotoneLite::bootstrapFlowTesting() was given initialTime: 2024-03-01 10:00:00.000000 +00:00 and a testConfiguration: pinned by withTimeAt() to 2024-03-01 11:00:00.000000 +00:00. A flow test starts its clock at one instant, so one of the two would be ignored. Pin the time once: pass initialTime: and remove withTimeAt() from the testConfiguration:, or keep withTimeAt() and drop initialTime:.');

        EcotoneLite::bootstrapFlowTesting(
            testConfiguration: TestConfiguration::createWithDefaults()->withTimeAt(new DateTimeImmutable('2024-03-01 11:00:00', new DateTimeZone('UTC'))),
            initialTime: new DateTimeImmutable('2024-03-01 10:00:00', new DateTimeZone('UTC')),
        );
    }
}
