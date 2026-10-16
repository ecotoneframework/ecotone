<?php

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
 */
declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Priority;
use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Projecting\FromStream;
use Ecotone\Api\Projecting\Partitioned;
use Ecotone\Api\Projecting\PartitionProvider as PartitionProviderAttribute;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\ProjectionBackfill;
use Ecotone\Api\Projecting\ProjectionDeployment;
use Ecotone\Api\Projecting\ProjectionExecution;
use Ecotone\Api\Projecting\ProjectionFlush;
use Ecotone\Api\Projecting\ProjectionInitialization;
use Ecotone\Api\Projecting\StateStorage;
use Ecotone\Api\Projecting\StreamSource as StreamSourceAttribute;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Endpoint\Interceptor\PcntlTerminationListener;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Projecting\NoOpTransaction;
use Ecotone\Projecting\PartitionProvider;
use Ecotone\Projecting\ProjectionInitializationStatus;
use Ecotone\Projecting\ProjectionPartitionState;
use Ecotone\Projecting\ProjectionStateStorage;
use Ecotone\Projecting\StreamFilter;
use Ecotone\Projecting\StreamPage;
use Ecotone\Projecting\StreamSource;
use Ecotone\Projecting\Transaction;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 */
class ProjectingTest extends TestCase
{
    public function test_asynchronous_projection(): void
    {
        // Given an asynchronous projection
        $projection = new #[Projection('test'), FromStream('test_stream'), Asynchronous('async')] class {
            public array $handledEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->handledEvents[] = $event;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([Event::createWithType('test-event', ['name' => 'Test'])]);

        // When event is published, triggering the projection
        $ecotone->publishEventWithRouting('trigger', []);

        // Then it is not handled until async channel is run
        $this->assertCount(0, $projection->handledEvents);
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertCount(1, $projection->handledEvents);
    }

    public function test_partitioned_projection(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), Partitioned('partitionHeader')] class {
            public array $handledEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->handledEvents[] = $event;
            }
        };

        $streamSource = new #[StreamSourceAttribute] class () implements StreamSource {
            private array $events = [];
            private string $partitionHeader = 'id';

            public function append(Event ...$events): void
            {
                foreach ($events as $event) {
                    $this->events[] = $event;
                }
            }

            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function load(string $projectionName, ?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
            {
                $from = $lastPosition !== null ? (int) $lastPosition : 0;
                if ($partitionKey) {
                    $events = array_filter($this->events, fn (Event $event) => $event->metadata[$this->partitionHeader] === $partitionKey);
                    $events = array_values($events);
                } else {
                    $events = $this->events;
                }
                $events = array_slice($events, $from, $count);
                $to = $from + count($events);
                return new StreamPage($events, (string) $to);
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $streamSource::class],
            [$projection, $streamSource],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $streamSource->append(
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '1']),
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '2']),
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '1']),
        );

        $ecotone->publishEventWithRouting('trigger', metadata: ['partitionHeader' => '1']);

        $this->assertCount(2, $projection->handledEvents);
    }

    public function test_asynchronous_partitioned_projection(): void
    {
        $projection = new #[Projection('test'), FromStream('test_stream'), Partitioned('partitionHeader'), Asynchronous('async')] class {
            public array $handledEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->handledEvents[] = $event;
            }
        };

        $streamSource = new #[StreamSourceAttribute] class () implements StreamSource {
            private array $events = [];
            private string $partitionHeader = 'id';

            public function append(Event ...$events): void
            {
                foreach ($events as $event) {
                    $this->events[] = $event;
                }
            }

            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function load(string $projectionName, ?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
            {
                $from = $lastPosition !== null ? (int) $lastPosition : 0;
                if ($partitionKey) {
                    $events = array_filter($this->events, fn (Event $event) => $event->metadata[$this->partitionHeader] === $partitionKey);
                    $events = array_values($events);
                } else {
                    $events = $this->events;
                }
                $events = array_slice($events, $from, $count);
                $to = $from + count($events);
                return new StreamPage($events, (string) $to);
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $streamSource::class],
            [$projection, $streamSource],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $streamSource->append(
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '1']),
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '2']),
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '1']),
        );

        $ecotone->publishEventWithRouting('trigger', metadata: ['partitionHeader' => '1']);

        $this->assertCount(0, $projection->handledEvents);
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertCount(2, $projection->handledEvents);
    }

    public function test_it_can_init_projection_lifecycle_state(): void
    {
        $projection = new #[Projection('projection_with_lifecycle'), FromStream('test_stream')] class {
            public const TICKET_CREATED = 'ticket.created';
            private bool $initialized = false;
            public array $projectedEvents = [];

            #[EventHandler(self::TICKET_CREATED)]
            public function on(array $event): void
            {
                if (! $this->initialized) {
                    throw new RuntimeException('Projection not initialized');
                }
                $this->projectedEvents[] = $event;
            }

            #[ProjectionInitialization]
            public function init(): void
            {
                if ($this->initialized) {
                    throw new RuntimeException('Projection already initialized');
                }
                $this->initialized = true;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-4']),
        ]);
        self::assertEquals([], $projection->projectedEvents);

        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);
        self::assertCount(2, $projection->projectedEvents);
    }

    public function test_it_skips_execution_when_automatic_initialization_is_off_and_not_initialized(): void
    {
        $projection = new #[Projection('projection_with_manual_initialization'), FromStream('test_stream'), ProjectionDeployment(manualKickOff: true)] class {
            public const TICKET_CREATED = 'ticket.created';
            public array $projectedEvents = [];

            #[EventHandler(self::TICKET_CREATED)]
            public function on(array $event): void
            {
                $this->projectedEvents[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
        ]);

        // Event trigger should be skipped when not initialized
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);
        self::assertCount(0, $projection->projectedEvents, 'Projection should not process events when automatic initialization is off');

        $ecotone->triggerProjection('projection_with_manual_initialization');
        self::assertCount(1, $projection->projectedEvents, 'Projection should have processed previous events after manual initialization');

        // Now events should be processed since projection is initialized
        $ecotone->withEvents([
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']),
        ]);
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']);
        self::assertCount(3, $projection->projectedEvents, 'Projection should process events after manual initialization');
    }

    public function test_init_partition_concurrency_protection(): void
    {
        $projection = new #[Projection('concurrent_projection'), FromStream('test_stream')] class {
            public const TICKET_CREATED = 'ticket.created';
            public array $projectedEvents = [];
            public int $initCallCount = 0;

            #[EventHandler(self::TICKET_CREATED)]
            public function on(array $event): void
            {
                $this->projectedEvents[] = $event;
            }

            #[ProjectionInitialization]
            public function init(): void
            {
                $this->initCallCount++;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        // Add all events to the stream first
        $ecotone->withEvents([
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']),
        ]);

        // Trigger the first event which should initialize the projection and process all events
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);

        // The init method should only be called once due to initPartition concurrency protection
        self::assertEquals(1, $projection->initCallCount, 'Init should only be called once due to initPartition concurrency protection');
        // All events should be processed during the first execution
        self::assertCount(3, $projection->projectedEvents, 'All events should be processed');
    }

    public function test_auto_initialization_mode_processes_events(): void
    {
        $projection = new #[Projection('auto_projection'), FromStream('test_stream'), ProjectionDeployment(manualKickOff: false)] class {
            public const TICKET_CREATED = 'ticket.created';
            public array $projectedEvents = [];
            public int $initCallCount = 0;

            #[EventHandler(self::TICKET_CREATED)]
            public function on(array $event): void
            {
                $this->projectedEvents[] = $event;
            }

            #[ProjectionInitialization]
            public function init(): void
            {
                $this->initCallCount++;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        // Add events to stream
        $ecotone->withEvents([
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
        ]);

        // Trigger event - should auto-initialize and process events
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);

        self::assertEquals(1, $projection->initCallCount, 'Init should be called once');
        self::assertCount(2, $projection->projectedEvents, 'All events should be processed in auto mode');
    }

    public function test_skip_initialization_mode_skips_events_when_not_initialized(): void
    {
        $projection = new #[Projection('skip_projection'), FromStream('test_stream'), ProjectionDeployment(manualKickOff: true)] class {
            public const TICKET_CREATED = 'ticket.created';
            public array $projectedEvents = [];
            public int $initCallCount = 0;

            #[EventHandler(self::TICKET_CREATED)]
            public function on(array $event): void
            {
                $this->projectedEvents[] = $event;
            }

            #[ProjectionInitialization]
            public function init(): void
            {
                $this->initCallCount++;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        // Add events to stream
        $ecotone->withEvents([
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
        ]);

        // Trigger event - should skip processing since not initialized
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);

        self::assertEquals(0, $projection->initCallCount, 'Init should not be called in skip mode');
        self::assertCount(0, $projection->projectedEvents, 'No events should be processed in skip mode');
    }

    public function test_skip_mode_with_multiple_events(): void
    {
        $projection = new #[Projection('skip_multiple_events'), FromStream('test_stream'), ProjectionDeployment(manualKickOff: true)] class {
            public const TICKET_CREATED = 'ticket.created';
            public array $projectedEvents = [];
            public int $initCallCount = 0;

            #[EventHandler(self::TICKET_CREATED)]
            public function on(array $event): void
            {
                $this->projectedEvents[] = $event;
            }

            #[ProjectionInitialization]
            public function init(): void
            {
                $this->initCallCount++;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        // Add multiple events to stream
        $ecotone->withEvents([
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']),
        ]);

        // Trigger multiple events - all should be skipped
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']);
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']);

        self::assertEquals(0, $projection->initCallCount, 'Init should not be called in skip mode');
        self::assertCount(0, $projection->projectedEvents, 'No events should be processed in skip mode');
    }

    public function test_auto_mode_with_multiple_events(): void
    {
        $projection = new #[Projection('auto_multiple_events'), FromStream('test_stream'), ProjectionDeployment(manualKickOff: false)] class {
            public const TICKET_CREATED = 'ticket.created';
            public array $projectedEvents = [];
            public int $initCallCount = 0;

            #[EventHandler(self::TICKET_CREATED)]
            public function on(array $event): void
            {
                $this->projectedEvents[] = $event;
            }

            #[ProjectionInitialization]
            public function init(): void
            {
                $this->initCallCount++;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        // Add multiple events to stream
        $ecotone->withEvents([
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']),
        ]);

        // Trigger first event - should auto-initialize and process all events
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);

        self::assertEquals(1, $projection->initCallCount, 'Init should be called once in auto mode');
        self::assertCount(3, $projection->projectedEvents, 'All events should be processed in auto mode');
    }

    public function test_projection_with_partitioned_events(): void
    {
        $projection = new #[Projection('partitioned_auto_projection'), FromStream('test_stream'), Partitioned('tenantId')] class {
            public const TICKET_CREATED = 'ticket.created';
            public array $projectedEvents = [];
            public int $initCallCount = 0;

            #[EventHandler(self::TICKET_CREATED)]
            public function on(array $event): void
            {
                $this->projectedEvents[] = $event;
            }

            #[ProjectionInitialization]
            public function init(): void
            {
                $this->initCallCount++;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        // Add events for different partitions
        $ecotone->withEvents([
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1', 'tenantId' => 'tenant-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2', 'tenantId' => 'tenant-2']),
        ]);

        // Trigger event for first partition - should initialize and process all events
        $ecotone->publishEventWithRouting($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1', 'tenantId' => 'tenant-1']);

        self::assertEquals(1, $projection->initCallCount, 'Init should be called once for partitioned projection');
        self::assertCount(2, $projection->projectedEvents, 'All events should be processed for partitioned projection');
    }

    public function test_it_throws_exception_when_no_licence(): void
    {
        $this->expectException(LicensingException::class);

        $projection = new #[Projection('test'), FromStream('test_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };

        $streamSource = new #[StreamSourceAttribute] class () implements StreamSource {
            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function load(string $projectionName, ?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
            {
                return new StreamPage([], '0');
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $streamSource::class],
            [$projection, $streamSource],
            enabledModules: [],
        );
    }

    public function test_it_throws_exception_when_no_stream_defined(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Projection 'test_no_stream' must have at least one #[FromStream] or #[FromAggregateStream] attribute defined");

        $projection = new #[Projection('test_no_stream')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };
        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
    }

    public function test_it_with_event_handler_priority(): void
    {
        $db = [];
        $projectionA = new #[Projection('A'), FromStream('test_stream')] class ($db) {
            public function __construct(private array &$db)
            {
            }
            #[EventHandler('no-priority')]
            public function handle(array $event): void
            {
                $this->db[] = 'projectionA-no-priority';
            }
            #[Priority(-42)]
            #[EventHandler('with-priority')]
            public function handleHighPriority(array $event): void
            {
                $this->db[] = 'projectionA-with-priority';
            }
        };
        $projectionB = new #[Projection('B'), FromStream('test_stream')] class ($db) {
            public function __construct(private array &$db)
            {
            }

            #[EventHandler('no-priority')]
            public function handle(array $event): void
            {
                $this->db[] = 'projectionB-no-priority';
            }
            #[Priority(10)]
            #[EventHandler('with-priority')]
            public function handleHighPriority(array $event): void
            {
                $this->db[] = 'projectionB-with-priority';
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projectionA::class, $projectionB::class],
            [$projectionA, $projectionB],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([
            Event::createWithType('no-priority', []),
        ]);

        $ecotone->publishEventWithRouting('no-priority');
        self::assertEquals(['projectionA-no-priority', 'projectionB-no-priority'], $db);

        $db = [];
        $ecotone->withEvents([
            Event::createWithType('with-priority', []),
        ]);
        $ecotone->publishEventWithRouting('with-priority');
        self::assertEquals(['projectionB-with-priority', 'projectionA-with-priority'], $db);
    }

    public function test_it_can_flush_by_batches(): void
    {
        $projection = new #[Projection('batch_projection'), FromStream('test_stream')] class () {
            public array $processingEvents = [];
            public array $flushedEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->processingEvents[] = $event;
            }

            #[ProjectionFlush]
            public function flush(): void
            {
                $this->flushedEvents[] = $this->processingEvents;
                $this->processingEvents = [];
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        $ecotone->withEvents([
            Event::createWithType('event1', []),
            Event::createWithType('event2', []),
            Event::createWithType('event3', []),
            Event::createWithType('event4', []),
            Event::createWithType('event5', []),
        ]);

        $ecotone->triggerProjection('batch_projection');
        self::assertCount(1, $projection->flushedEvents);
        self::assertCount(5, $projection->flushedEvents[0]);
        self::assertCount(0, $projection->processingEvents);
    }

    public function test_trigger_projection_processes_events_synchronously_even_with_async_backfill_channel_configured(): void
    {
        $projection = new #[Projection('sync_trigger_projection'), FromStream('test_stream'), ProjectionBackfill(asyncChannelName: 'backfill_async')] class {
            public array $processedEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->processedEvents[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('backfill_async')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        $ecotone->withEvents([
            Event::createWithType('event1', []),
        ]);

        $ecotone->triggerProjection('sync_trigger_projection');

        self::assertCount(1, $projection->processedEvents, 'triggerProjection() must process pending events synchronously, not merely enqueue a backfill batch');
    }

    public function test_reset_projection_replays_previously_processed_events_from_scratch(): void
    {
        $projection = new #[Projection('reset_replay_projection'), FromStream('test_stream')] class {
            public array $processedEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->processedEvents[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        $ecotone->withEvents([
            Event::createWithType('event1', []),
        ]);
        $ecotone->triggerProjection('reset_replay_projection');
        self::assertCount(1, $projection->processedEvents);

        $ecotone->resetProjection('reset_replay_projection');

        self::assertCount(2, $projection->processedEvents, 'resetProjection() must replay previously processed events from scratch, not merely clear and re-init state');
    }

    #[RequiresPhpExtension('pcntl')]
    #[RequiresPhpExtension('posix')]
    public function test_pcntl_signals_handling(): void
    {
        $projection = new #[Projection('signals_projection'), FromStream('test_stream'), ProjectionExecution(eventLoadingBatchSize: 2)] class () {
            public array $processedEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                posix_kill(posix_getpid(), SIGTERM);
                $this->processedEvents[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        $ecotone->withEvents([
            Event::createWithType('event1', []),
            Event::createWithType('event2', []),
            Event::createWithType('event3', []),
            Event::createWithType('event4', []),
            Event::createWithType('event5', []),
        ]);
        /** @var PcntlTerminationListener $pcntlTerminationFlag */
        $pcntlTerminationFlag = $ecotone->getServiceFromContainer(PcntlTerminationListener::class);
        $pcntlTerminationFlag->enable();
        try {
            $ecotone->triggerProjection('signals_projection');
            self::assertCount(2, $projection->processedEvents, 'Expect projection to process a single batch of events (2 events)');
        } finally {
            $pcntlTerminationFlag->disable();
        }
    }

    public function test_userland_partition_provider_is_used_during_backfill(): void
    {
        $userlandPartitionProvider = new #[PartitionProviderAttribute] class () implements PartitionProvider {
            public function canHandle(string $projectionName): bool
            {
                return $projectionName === 'userland_backfill_projection';
            }

            public function count(StreamFilter $filter): int
            {
                return 3;
            }

            public function partitions(StreamFilter $filter, ?int $limit = null, int $offset = 0): iterable
            {
                $partitions = ['partition-a', 'partition-b', 'partition-c'];
                $partitions = array_slice($partitions, $offset, $limit);
                yield from $partitions;
            }
        };

        $projection = new #[Projection('userland_backfill_projection'), FromStream('test_stream'), Partitioned('partitionHeader'), ProjectionBackfill(backfillPartitionBatchSize: 2, asyncChannelName: 'backfill_async')] class {
            public array $processedEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->processedEvents[] = $event;
            }
        };

        $streamSource = new #[StreamSourceAttribute] class () implements StreamSource {
            private array $events = [];
            private string $partitionHeader = 'partitionId';

            public function append(Event ...$events): void
            {
                foreach ($events as $event) {
                    $this->events[] = $event;
                }
            }

            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function load(string $projectionName, ?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
            {
                $from = $lastPosition !== null ? (int) $lastPosition : 0;
                if ($partitionKey) {
                    $events = array_filter($this->events, fn (Event $event) => $event->metadata[$this->partitionHeader] === $partitionKey);
                    $events = array_values($events);
                } else {
                    $events = $this->events;
                }
                $events = array_slice($events, $from, $count);
                $to = $from + count($events);
                return new StreamPage($events, (string) $to);
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $userlandPartitionProvider::class, $streamSource::class],
            [$projection, $userlandPartitionProvider, $streamSource],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('backfill_async')],
            enabledModules: [],
            addInMemoryStateStoredRepository: false,
            testConfiguration: \Ecotone\Api\ExtensionObject\TestConfiguration::createWithDefaults()->withSpyOnChannel('backfill_async'),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $streamSource->append(
            Event::createWithType('test-event', ['name' => 'Test'], ['partitionId' => 'partition-a']),
            Event::createWithType('test-event', ['name' => 'Test'], ['partitionId' => 'partition-b']),
            Event::createWithType('test-event', ['name' => 'Test'], ['partitionId' => 'partition-c']),
        );

        $ecotone->initializeProjection('userland_backfill_projection');
        $ecotone->runConsoleCommand('ecotone:projection:backfill', ['name' => 'userland_backfill_projection']);

        $messages = $ecotone->popRecordedMessagePayloadsFrom('backfill_async');
        self::assertCount(2, $messages, 'Expected 2 batches for 3 partitions with batch size 2');
    }

    public function test_non_handled_projection_falls_back_to_single_partition_during_backfill(): void
    {
        $userlandPartitionProvider = new #[PartitionProviderAttribute] class () implements PartitionProvider {
            public function canHandle(string $projectionName): bool
            {
                return $projectionName === 'only_this_projection';
            }

            public function count(StreamFilter $filter): int
            {
                return 10;
            }

            public function partitions(StreamFilter $filter, ?int $limit = null, int $offset = 0): iterable
            {
                yield from range(1, 10);
            }
        };

        $projection = new #[Projection('different_projection'), FromStream('test_stream'), ProjectionBackfill(asyncChannelName: 'backfill_async')] class {
            public array $processedEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->processedEvents[] = $event;
            }
        };

        $streamSource = new #[StreamSourceAttribute] class () implements StreamSource {
            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function load(string $projectionName, ?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
            {
                return new StreamPage([], '0');
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $userlandPartitionProvider::class, $streamSource::class],
            [$projection, $userlandPartitionProvider, $streamSource],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('backfill_async')],
            enabledModules: [],
            addInMemoryStateStoredRepository: false,
            testConfiguration: \Ecotone\Api\ExtensionObject\TestConfiguration::createWithDefaults()->withSpyOnChannel('backfill_async'),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->initializeProjection('different_projection');
        $ecotone->runConsoleCommand('ecotone:projection:backfill', ['name' => 'different_projection']);

        $messages = $ecotone->popRecordedMessagePayloadsFrom('backfill_async');
        self::assertCount(1, $messages, 'SinglePartitionProvider should produce exactly 1 batch');
    }

    public function test_interceptor_can_hook_into_flush_via_pointcut(): void
    {
        $interceptor = new class () {
            public int $invocationCount = 0;

            #[Around(pointcut: ProjectionFlush::class)]
            public function intercept(MethodInvocation $methodInvocation): mixed
            {
                $this->invocationCount++;
                return $methodInvocation->proceed();
            }
        };

        $projection = new #[Projection('intercepted_batch_projection'), FromStream('test_stream'), ProjectionExecution(eventLoadingBatchSize: 3)] class () {
            public array $processedEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->processedEvents[] = $event;
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $interceptor::class],
            [$projection, $interceptor],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotone->withEvents([
            Event::createWithType('event1', []),
            Event::createWithType('event2', []),
            Event::createWithType('event3', []),
            Event::createWithType('event4', []),
            Event::createWithType('event5', []),
            Event::createWithType('event6', []),
            Event::createWithType('event7', []),
        ]);

        $ecotone->triggerProjection('intercepted_batch_projection');

        self::assertCount(7, $projection->processedEvents);
        self::assertEquals(4, $interceptor->invocationCount, 'Interceptor should be called once per batch (3+3+1 events + 1 empty batch check)');
    }

    public function test_userland_state_storage_is_prioritized_over_built_in(): void
    {
        $userlandStorage = new #[StateStorage] class () implements ProjectionStateStorage {
            public bool $wasUsed = false;
            private array $projectionStates = [];

            public function canHandle(string $projectionName): bool
            {
                return $projectionName === 'userland_storage_projection';
            }

            public function loadPartition(string $projectionName, ?string $partitionKey = null, bool $lock = true): ?ProjectionPartitionState
            {
                $this->wasUsed = true;
                $key = $projectionName . ($partitionKey ? '-' . $partitionKey : '');
                return $this->projectionStates[$key] ?? null;
            }

            public function initPartition(string $projectionName, ?string $partitionKey = null): ?ProjectionPartitionState
            {
                $this->wasUsed = true;
                $key = $projectionName . ($partitionKey ? '-' . $partitionKey : '');
                if (! isset($this->projectionStates[$key])) {
                    $this->projectionStates[$key] = new ProjectionPartitionState($projectionName, $partitionKey, null, null, ProjectionInitializationStatus::UNINITIALIZED);
                    return $this->projectionStates[$key];
                }
                return null;
            }

            public function savePartition(ProjectionPartitionState $projectionState): void
            {
                $this->wasUsed = true;
                $key = $projectionState->projectionName . ($projectionState->partitionKey ? '-' . $projectionState->partitionKey : '');
                $this->projectionStates[$key] = $projectionState;
            }

            public function delete(string $projectionName): void
            {
                $this->wasUsed = true;
            }

            public function init(string $projectionName): void
            {
                $this->wasUsed = true;
            }

            public function beginTransaction(): Transaction
            {
                return new NoOpTransaction();
            }
        };

        $projection = new #[Projection('userland_storage_projection'), FromStream('test_stream')] class {
            public array $processedEvents = [];
            #[EventHandler('*')]
            public function handle(array $event): void
            {
                $this->processedEvents[] = $event;
            }
        };

        $streamSource = new #[StreamSourceAttribute] class () implements StreamSource {
            private array $events = [];

            public function append(Event ...$events): void
            {
                foreach ($events as $event) {
                    $this->events[] = $event;
                }
            }

            public function canHandle(string $projectionName): bool
            {
                return true;
            }

            public function load(string $projectionName, ?string $lastPosition, int $count, ?string $partitionKey = null): StreamPage
            {
                $from = $lastPosition !== null ? (int) $lastPosition : 0;
                $events = array_slice($this->events, $from, $count);
                $to = $from + count($events);
                return new StreamPage($events, (string) $to);
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $userlandStorage::class, $streamSource::class],
            [$projection, $userlandStorage, $streamSource],
            enabledModules: [],
            addInMemoryStateStoredRepository: false,
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $streamSource->append(
            Event::createWithType('test-event', ['name' => 'Test']),
        );

        $ecotone->initializeProjection('userland_storage_projection');

        self::assertTrue($userlandStorage->wasUsed, 'Userland state storage should be prioritized and used');
    }
}
