<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use DateTimeImmutable;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Scheduling\TimeSpan;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class DelayedMessageReleaseTest extends TestCase
{
    public function test_delayed_message_is_not_processed_before_it_is_released(): void
    {
        $handler = new DelayedMessageHandler();
        $ecotone = $this->bootstrap($handler);

        $ecotone->sendCommandWithRouting(DelayedMessageHandler::ROUTING_KEY, 'a', metadata: [
            MessageHeaders::DELIVERY_DELAY => 10_000,
        ]);

        $ecotone->advanceTimeBy(TimeSpan::withSeconds(5))->run(DelayedMessageHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));

        $this->assertSame([], $handler->processed);
    }

    public function test_releasing_processes_messages_that_have_reached_their_delay(): void
    {
        $handler = new DelayedMessageHandler();
        $ecotone = $this->bootstrap($handler);

        $ecotone->sendCommandWithRouting(DelayedMessageHandler::ROUTING_KEY, 'a', metadata: [
            MessageHeaders::DELIVERY_DELAY => 10_000,
        ]);

        $ecotone->advanceTimeBy(TimeSpan::withSeconds(10))->run(DelayedMessageHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));

        $this->assertSame(['a'], $handler->processed);
    }

    public function test_releasing_processes_multiple_delayed_messages_in_ascending_delay_order(): void
    {
        $handler = new DelayedMessageHandler();
        $ecotone = $this->bootstrap($handler);

        $ecotone->sendCommandWithRouting(DelayedMessageHandler::ROUTING_KEY, 'first', metadata: [MessageHeaders::DELIVERY_DELAY => 3000]);
        $ecotone->sendCommandWithRouting(DelayedMessageHandler::ROUTING_KEY, 'second', metadata: [MessageHeaders::DELIVERY_DELAY => 2000]);
        $ecotone->sendCommandWithRouting(DelayedMessageHandler::ROUTING_KEY, 'third', metadata: [MessageHeaders::DELIVERY_DELAY => 1000]);

        $ecotone->advanceTimeBy(TimeSpan::withSeconds(1))->run(DelayedMessageHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
        $this->assertSame(['third'], $handler->processed);

        $ecotone->advanceTimeBy(TimeSpan::withSeconds(1))->run(DelayedMessageHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
        $this->assertSame(['third', 'second'], $handler->processed);

        $ecotone->advanceTimeBy(TimeSpan::withSeconds(1))->run(DelayedMessageHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, stopOnError: false));
        $this->assertSame(['third', 'second', 'first'], $handler->processed);
    }

    private function bootstrap(DelayedMessageHandler $handler)
    {
        return EcotoneLite::bootstrapFlowTesting(
            [DelayedMessageHandler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                SimpleMessageChannelBuilder::createQueueChannel(DelayedMessageHandler::CHANNEL, delayable: true),
            ])
                ->withEnabledModulePackageNames([]),
        )->changeTimeTo(new DateTimeImmutable('2026-01-01 12:00:00'));
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class DelayedMessageHandler
{
    public const CHANNEL = 'delayedMessage.channel';
    public const ROUTING_KEY = 'delayedMessage.handle';

    public array $processed = [];

    #[Asynchronous(self::CHANNEL)]
    #[CommandHandler(self::ROUTING_KEY, 'delayedMessageHandler')]
    public function handle(string $payload): void
    {
        $this->processed[] = $payload;
    }
}
