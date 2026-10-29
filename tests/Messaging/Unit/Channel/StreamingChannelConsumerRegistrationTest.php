<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\Consumer\InMemory\InMemoryConsumerPositionTracker;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Modelling\Attribute\QueryHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class StreamingChannelConsumerRegistrationTest extends TestCase
{
    public function test_shared_channel_is_automatically_registered_as_consumer(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [],
            [ConsumerPositionTracker::class => $positionTracker],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('shared_channel'),
                ])
        );

        $this->assertContains('shared_channel', $ecotoneLite->list());
    }

    public function test_shared_channel_consumer_is_registered_when_used_by_handler(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        $handler = new class () {
            private array $consumed = [];

            #[InternalHandler(inputChannelName: 'shared_channel', endpointId: 'consumer1')]
            public function handle(string $payload): void
            {
                $this->consumed[] = $payload;
            }

            #[QueryHandler('getConsumed')]
            public function getConsumed(): array
            {
                return $this->consumed;
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler, ConsumerPositionTracker::class => $positionTracker],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('shared_channel'),
                    PollingMetadata::create('consumer1')->setHandledMessageLimit(1),
                ])
        );

        // Consumer should be registered when handler uses the shared channel
        $this->assertContains('consumer1', $ecotoneLite->list());

        // Verify it works
        $ecotoneLite->sendDirectToChannel('shared_channel', 'message1');
        $ecotoneLite->run('consumer1', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 1));
        $this->assertEquals(['message1'], $ecotoneLite->sendQueryWithRouting('getConsumed'));
    }

    public function test_regular_pollable_channel_is_automatically_registered_as_consumer(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('regular_channel'),
                ])
        );

        // Regular pollable channel SHOULD be in the list of consumers
        $this->assertContains('regular_channel', $ecotoneLite->list());
    }

    public function test_multiple_consumers_with_different_message_group_ids_are_registered_separately(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        $handler1 = new class () {
            #[InternalHandler(inputChannelName: 'shared_channel', endpointId: 'consumer_group_1')]
            public function handle(string $payload): void
            {
            }
        };

        $handler2 = new class () {
            #[InternalHandler(inputChannelName: 'shared_channel', endpointId: 'consumer_group_2')]
            public function handle(string $payload): void
            {
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler1::class, $handler2::class],
            [$handler1, $handler2, ConsumerPositionTracker::class => $positionTracker],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('shared_channel'),
                    PollingMetadata::create('consumer_group_1')->setHandledMessageLimit(1),
                    PollingMetadata::create('consumer_group_2')->setHandledMessageLimit(1),
                ])
        );

        // Both consumers should be registered with their own endpoint IDs
        $this->assertContains('consumer_group_1', $ecotoneLite->list());
        $this->assertContains('consumer_group_2', $ecotoneLite->list());
    }

    public function test_in_memory_shared_channel_each_consumer_handles_messages_independently_with_separate_position_tracking(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        $handler1 = new class () {
            private array $consumed = [];

            #[InternalHandler(inputChannelName: 'shared_channel', endpointId: 'consumer_group_1')]
            public function handle(string $payload): void
            {
                $this->consumed[] = $payload;
            }

            #[QueryHandler('getConsumed1')]
            public function getConsumed(): array
            {
                return $this->consumed;
            }
        };

        $handler2 = new class () {
            private array $consumed = [];

            #[InternalHandler(inputChannelName: 'shared_channel', endpointId: 'consumer_group_2')]
            public function handle(string $payload): void
            {
                $this->consumed[] = $payload;
            }

            #[QueryHandler('getConsumed2')]
            public function getConsumed(): array
            {
                return $this->consumed;
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$handler1::class, $handler2::class],
            [$handler1, $handler2, ConsumerPositionTracker::class => $positionTracker],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createStreamingChannel('shared_channel'),
                    PollingMetadata::create('consumer_group_1')->setHandledMessageLimit(1),
                    PollingMetadata::create('consumer_group_2')->setHandledMessageLimit(1),
                ])
        );

        // Send messages to the shared channel
        $ecotoneLite->sendDirectToChannel('shared_channel', 'message1');
        $ecotoneLite->sendDirectToChannel('shared_channel', 'message2');
        $ecotoneLite->sendDirectToChannel('shared_channel', 'message3');

        // Consumer 1 processes first message
        $ecotoneLite->run('consumer_group_1', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 1));
        $this->assertEquals(['message1'], $ecotoneLite->sendQueryWithRouting('getConsumed1'));
        $this->assertEquals([], $ecotoneLite->sendQueryWithRouting('getConsumed2'));

        // Consumer 2 processes first message (should get the same message1 because it tracks its own position)
        $ecotoneLite->run('consumer_group_2', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 1));
        $this->assertEquals(['message1'], $ecotoneLite->sendQueryWithRouting('getConsumed1'));
        $this->assertEquals(['message1'], $ecotoneLite->sendQueryWithRouting('getConsumed2'));

        // Consumer 1 processes second message
        $ecotoneLite->run('consumer_group_1', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 1));
        $this->assertEquals(['message1', 'message2'], $ecotoneLite->sendQueryWithRouting('getConsumed1'));
        $this->assertEquals(['message1'], $ecotoneLite->sendQueryWithRouting('getConsumed2'));

        // Consumer 2 processes second and third messages
        $ecotoneLite->run('consumer_group_2', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 2));
        $this->assertEquals(['message1', 'message2'], $ecotoneLite->sendQueryWithRouting('getConsumed1'));
        $this->assertEquals(['message1', 'message2', 'message3'], $ecotoneLite->sendQueryWithRouting('getConsumed2'));

        // Consumer 1 processes third message
        $ecotoneLite->run('consumer_group_1', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 1));
        $this->assertEquals(['message1', 'message2', 'message3'], $ecotoneLite->sendQueryWithRouting('getConsumed1'));
        $this->assertEquals(['message1', 'message2', 'message3'], $ecotoneLite->sendQueryWithRouting('getConsumed2'));

        // Verify both consumers processed all messages independently - this proves position tracking works
        // because each consumer got all 3 messages despite consuming at different rates
    }
}
