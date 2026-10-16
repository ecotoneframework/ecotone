<?php

declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Projecting\EventStreamingChannelAdapter;
use Ecotone\Messaging\Consumer\ConsumerPositionTracker;
use Ecotone\Messaging\Consumer\InMemory\InMemoryConsumerPositionTracker;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EventStoreChannelAdapter with in-memory event store
 * @internal
 */
final class EventStoreChannelAdapterTest extends TestCase
{
    public function test_feeding_events_from_in_memory_event_store_to_streaming_channel(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        // Consumer that reads from the streaming channel
        $consumer = new class () {
            private array $consumed = [];

            #[InternalHandler(inputChannelName: 'event_stream', endpointId: 'stream_consumer')]
            public function handle(array $event): void
            {
                $this->consumed[] = $event;
            }

            #[QueryHandler('getConsumed')]
            public function getConsumed(): array
            {
                return $this->consumed;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$consumer::class],
            containerOrAvailableServices: [
                $consumer,
                ConsumerPositionTracker::class => $positionTracker,
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createStreamingChannel('event_stream'),
                EventStreamingChannelAdapter::create(streamChannelName:'event_stream', endpointId:'event_store_feeder', fromStream:'test_stream'),
                PollingMetadata::create('stream_consumer')->withTestingSetup(),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        // When events are appended to the stream source
        $ecotone->withEventStream('test_stream', [
            Event::createWithType('ticket.registered', ['ticketId' => 'ticket-1', 'assignedPerson' => 'John'], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType('ticket.registered', ['ticketId' => 'ticket-2', 'assignedPerson' => 'Jane'], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
        ]);

        // When feeder runs (polls event store and pushes to streaming channel)
        $ecotone->run('event_store_feeder', ExecutionPollingMetadata::createWithTestingSetup());

        // When stream consumer runs
        $ecotone->run('stream_consumer', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 2));

        // Then events are consumed from streaming channel
        $consumedEvents = $ecotone->sendQueryWithRouting('getConsumed');
        $this->assertCount(2, $consumedEvents);
        $this->assertEquals('ticket-1', $consumedEvents[0]['ticketId']);
        $this->assertEquals('ticket-2', $consumedEvents[1]['ticketId']);
    }

    public function test_filtering_events_by_name_using_glob_patterns(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        // Consumer that reads from the streaming channel
        $consumer = new class () {
            private array $consumed = [];

            #[InternalHandler(inputChannelName: 'event_stream', endpointId: 'stream_consumer')]
            public function handle(array $event): void
            {
                $this->consumed[] = $event;
            }

            #[QueryHandler('getConsumed')]
            public function getConsumed(): array
            {
                return $this->consumed;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$consumer::class],
            containerOrAvailableServices: [
                $consumer,
                ConsumerPositionTracker::class => $positionTracker,
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createStreamingChannel('event_stream'),
                EventStreamingChannelAdapter::create(streamChannelName:'event_stream', endpointId:'event_store_feeder', fromStream:'test_stream')->withEventNames(['ticket.registered']),
                PollingMetadata::create('stream_consumer')->withTestingSetup(),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        // When events are appended to the stream source
        $ecotone->withEventStream('test_stream', [
            Event::createWithType('ticket.registered', ['ticketId' => 'ticket-1'], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType('ticket.closed', ['ticketId' => 'ticket-1'], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType('ticket.registered', ['ticketId' => 'ticket-2'], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
        ]);

        // When feeder runs
        $ecotone->run('event_store_feeder', ExecutionPollingMetadata::createWithTestingSetup());

        // When stream consumer runs
        $ecotone->run('stream_consumer', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 2));

        // Then only registered events are consumed (closed event is filtered out)
        $consumedEvents = $ecotone->sendQueryWithRouting('getConsumed');
        $this->assertCount(2, $consumedEvents, 'Should only consume ticket.registered events');
        $this->assertEquals('ticket-1', $consumedEvents[0]['ticketId']);
        $this->assertEquals('ticket-2', $consumedEvents[1]['ticketId']);
    }

    public function test_normal_event_handler_works_alongside_event_store_channel_adapter(): void
    {
        $positionTracker = new InMemoryConsumerPositionTracker();

        // Normal event handler that counts tickets (not a projection, just a simple event handler)
        // This demonstrates that EventStoreChannelAdapter works alongside normal event handlers
        $ticketCounter = new class () {
            public int $registeredCount = 0;
            public int $closedCount = 0;

            #[EventHandler('ticket.registered')]
            public function whenRegistered(array $event): void
            {
                $this->registeredCount++;
            }

            #[EventHandler('ticket.closed')]
            public function whenClosed(array $event): void
            {
                $this->closedCount++;
            }

            #[QueryHandler('getTicketCounts')]
            public function getCounts(): array
            {
                return [
                    'registered' => $this->registeredCount,
                    'closed' => $this->closedCount,
                ];
            }
        };

        // Consumer that reads from the streaming channel
        $consumer = new class () {
            private array $consumed = [];

            #[InternalHandler(inputChannelName: 'event_stream', endpointId: 'stream_consumer')]
            public function handle(array $event): void
            {
                $this->consumed[] = $event;
            }

            #[QueryHandler('getConsumed')]
            public function getConsumed(): array
            {
                return $this->consumed;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            classesToResolve: [$ticketCounter::class, $consumer::class],
            containerOrAvailableServices: [
                $ticketCounter,
                $consumer,
                ConsumerPositionTracker::class => $positionTracker,
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createStreamingChannel('event_stream'),
                EventStreamingChannelAdapter::create(streamChannelName:'event_stream', endpointId:'event_store_feeder', fromStream:'test_stream'),
                PollingMetadata::create('stream_consumer')->withTestingSetup(),
            ],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        // When events are published via Event Bus (which triggers event handlers)
        $ecotone->publishEventWithRouting('ticket.registered', ['ticketId' => 'ticket-1'], metadata: [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);
        $ecotone->publishEventWithRouting('ticket.registered', ['ticketId' => 'ticket-2'], metadata: [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']);
        $ecotone->publishEventWithRouting('ticket.closed', ['ticketId' => 'ticket-1'], metadata: [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);

        // Then normal event handler processes all events synchronously (event-driven by default)
        $counts = $ecotone->sendQueryWithRouting('getTicketCounts');
        $this->assertEquals(2, $counts['registered'], 'Event handler should have processed 2 ticket.registered events');
        $this->assertEquals(1, $counts['closed'], 'Event handler should have processed 1 ticket.closed event');

        // And events are also stored in stream source (for polling projection to consume)
        $ecotone->withEventStream('test_stream', [
            Event::createWithType('ticket.registered', ['ticketId' => 'ticket-1'], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType('ticket.registered', ['ticketId' => 'ticket-2'], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
            Event::createWithType('ticket.closed', ['ticketId' => 'ticket-1'], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
        ]);

        // When feeder runs (polls event store and pushes to streaming channel)
        $ecotone->run('event_store_feeder', ExecutionPollingMetadata::createWithTestingSetup());

        // When stream consumer runs (handle 3 messages)
        $ecotone->run('stream_consumer', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 3));

        // Then events are also consumed from streaming channel (as arrays)
        $consumedEvents = $ecotone->sendQueryWithRouting('getConsumed');
        $this->assertCount(3, $consumedEvents, 'Should have consumed 3 events from streaming channel');
        $this->assertEquals('ticket-1', $consumedEvents[0]['ticketId']);
        $this->assertEquals('ticket-2', $consumedEvents[1]['ticketId']);
        $this->assertEquals('ticket-1', $consumedEvents[2]['ticketId']); // closed event
    }
}
