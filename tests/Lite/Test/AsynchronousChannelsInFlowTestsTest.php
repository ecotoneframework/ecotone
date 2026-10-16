<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use DateTimeImmutable;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Delayed;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Messaging\Config\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class AsynchronousChannelsInFlowTestsTest extends TestCase
{
    public function test_asynchronous_handler_is_consumed_from_an_in_memory_channel_without_configuring_it(): void
    {
        $handler = $this->notificationHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            enabledModules: [],
        );

        $ecotone->publishEventWithRouting('order.placed', 'order-1');
        $this->assertSame([], $handler->notified);

        $ecotone->run('notifications');
        $this->assertSame(['order-1'], $handler->notified);
    }

    public function test_in_memory_channel_provided_for_a_test_honours_delays(): void
    {
        $handler = new class () {
            public array $expired = [];

            #[Asynchronous('async')]
            #[Delayed(new TimeSpan(hours: 1))]
            #[EventHandler('order.placed', endpointId: 'expireOrder')]
            public function expire(string $orderId): void
            {
                $this->expired[] = $orderId;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            enabledModules: [],
        );
        $ecotone->changeTimeTo(new DateTimeImmutable('2026-03-01 12:00:00'));

        $ecotone->publishEventWithRouting('order.placed', 'order-1')->run('async');
        $this->assertSame([], $handler->expired);

        $ecotone->advanceTimeBy(TimeSpan::withHours(1))->run('async');
        $this->assertSame(['order-1'], $handler->expired);
    }

    public function test_channel_configured_in_the_test_replaces_the_provided_one(): void
    {
        $handler = new class () {
            public array $expired = [];

            #[Asynchronous('async')]
            #[Delayed(new TimeSpan(hours: 1))]
            #[EventHandler('order.placed', endpointId: 'expireOrder')]
            public function expire(string $orderId): void
            {
                $this->expired[] = $orderId;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async', delayable:false)],
            enabledModules: [],
        );

        $ecotone->publishEventWithRouting('order.placed', 'order-1')->run('async');

        $this->assertSame(['order-1'], $handler->expired);
    }

    public function test_application_bootstrap_still_requires_the_asynchronous_channel_to_be_configured(): void
    {
        $handler = $this->notificationHandler();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Registered asynchronous endpoint `notifyAboutOrder`, however channel configuration for `notifications` was not provided.');

        EcotoneLite::bootstrap(
            [$handler::class],
            [$handler],
            enabledModules: [],
        );
    }

    private function notificationHandler(): object
    {
        return new class () {
            public array $notified = [];

            #[Asynchronous('notifications')]
            #[EventHandler('order.placed', endpointId: 'notifyAboutOrder')]
            public function notify(string $orderId): void
            {
                $this->notified[] = $orderId;
            }
        };
    }
}
