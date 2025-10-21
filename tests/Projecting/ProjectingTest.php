<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Test\Ecotone\Projecting;

use Ecotone\EventSourcing\Attribute\ProjectionInitialization;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\Endpoint\Priority;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Event;
use Ecotone\Projecting\Attribute\Projection;
use Ecotone\Projecting\InMemory\InMemoryStreamSourceBuilder;
use Ecotone\Test\LicenceTesting;
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
        $projection = new #[Projection('test'), Asynchronous('async')] class {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
                ->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async'))
        );

        $streamSource->append(Event::createWithType('test-event', ['name' => 'Test']));

        // When event is published, triggering the projection
        $ecotone->publishEventWithRoutingKey('trigger', []);

        // Then it is not handled until async channel is run
        $this->assertCount(0, $projection->handledEvents);
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertCount(1, $projection->handledEvents);
    }

    public function test_partitioned_projection(): void
    {
        // Given a partitioned projection
        $projection = new #[Projection('test', 'partitionHeader')] class {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder(partitionField: 'id'))
        );

        $streamSource->append(
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '1']),
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '2']),
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '1']),
        );

        // When event is published, triggering the projection
        $ecotone->publishEventWithRoutingKey('trigger', metadata: ['partitionHeader' => '1']);

        // Then only events from partition 1 are handled
        $this->assertCount(2, $projection->handledEvents);
    }

    public function test_asynchronous_partitioned_projection(): void
    {
        // Given a partitioned async projection
        $projection = new #[Projection('test', 'partitionHeader'), Asynchronous('async')] class {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder(partitionField: 'id'))
                ->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async'))
        );

        $streamSource->append(
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '1']),
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '2']),
            Event::createWithType('test-event', ['name' => 'Test'], ['id' => '1']),
        );

        // When event is published, triggering the projection
        $ecotone->publishEventWithRoutingKey('trigger', metadata: ['partitionHeader' => '1']);

        // Then no event is handled until async channel is run
        $this->assertCount(0, $projection->handledEvents);
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertCount(2, $projection->handledEvents);
    }

    public function test_it_can_init_projection_lifecycle_state(): void
    {
        $projection = new #[Projection('projection_with_lifecycle')] class {
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
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );

        $streamSource->append(
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-4']),
        );
        self::assertEquals([], $projection->projectedEvents);

        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);
        self::assertCount(2, $projection->projectedEvents);
    }

    public function test_it_skips_execution_when_automatic_initialization_is_off_and_not_initialized(): void
    {
        $projection = new #[Projection('projection_with_manual_initialization', automaticInitialization: false)] class {
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
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );

        $streamSource->append(
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
        );

        // Event trigger should be skipped when not initialized
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);
        self::assertCount(0, $projection->projectedEvents, 'Projection should not process events when automatic initialization is off');

        $ecotone->triggerProjection('projection_with_manual_initialization');
        self::assertCount(1, $projection->projectedEvents, 'Projection should have processed previous events after manual initialization');

        // Now events should be processed since projection is initialized
        $streamSource->append(
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']),
        );
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']);
        self::assertCount(3, $projection->projectedEvents, 'Projection should process events after manual initialization');
    }

    public function test_init_partition_concurrency_protection(): void
    {
        $projection = new #[Projection('concurrent_projection')] class {
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
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );

        // Add all events to the stream first
        $streamSource->append(
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']),
        );

        // Trigger the first event which should initialize the projection and process all events
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);

        // The init method should only be called once due to initPartition concurrency protection
        self::assertEquals(1, $projection->initCallCount, 'Init should only be called once due to initPartition concurrency protection');
        // All events should be processed during the first execution
        self::assertCount(3, $projection->projectedEvents, 'All events should be processed');
    }

    public function test_auto_initialization_mode_processes_events(): void
    {
        $projection = new #[Projection('auto_projection', automaticInitialization: true)] class {
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
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );

        // Add events to stream
        $streamSource->append(
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
        );

        // Trigger event - should auto-initialize and process events
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);

        self::assertEquals(1, $projection->initCallCount, 'Init should be called once');
        self::assertCount(2, $projection->projectedEvents, 'All events should be processed in auto mode');
    }

    public function test_skip_initialization_mode_skips_events_when_not_initialized(): void
    {
        $projection = new #[Projection('skip_projection', automaticInitialization: false)] class {
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
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );

        // Add events to stream
        $streamSource->append(
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
        );

        // Trigger event - should skip processing since not initialized
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);

        self::assertEquals(0, $projection->initCallCount, 'Init should not be called in skip mode');
        self::assertCount(0, $projection->projectedEvents, 'No events should be processed in skip mode');
    }

    public function test_skip_mode_with_multiple_events(): void
    {
        $projection = new #[Projection('skip_multiple_events', automaticInitialization: false)] class {
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
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );

        // Add multiple events to stream
        $streamSource->append(
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']),
        );

        // Trigger multiple events - all should be skipped
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']);
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']);

        self::assertEquals(0, $projection->initCallCount, 'Init should not be called in skip mode');
        self::assertCount(0, $projection->projectedEvents, 'No events should be processed in skip mode');
    }

    public function test_auto_mode_with_multiple_events(): void
    {
        $projection = new #[Projection('auto_multiple_events', automaticInitialization: true)] class {
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
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );

        // Add multiple events to stream
        $streamSource->append(
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-3']),
        );

        // Trigger first event - should auto-initialize and process all events
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1']);

        self::assertEquals(1, $projection->initCallCount, 'Init should be called once in auto mode');
        self::assertCount(3, $projection->projectedEvents, 'All events should be processed in auto mode');
    }

    public function test_projection_with_partitioned_events(): void
    {
        $projection = new #[Projection('partitioned_auto_projection', partitionHeaderName: 'tenantId', automaticInitialization: true)] class {
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
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );

        // Add events for different partitions
        $streamSource->append(
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1', 'tenantId' => 'tenant-1']),
            Event::createWithType($projection::TICKET_CREATED, [], [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-2', 'tenantId' => 'tenant-2']),
        );

        // Trigger event for first partition - should initialize and process all events
        $ecotone->publishEventWithRoutingKey($projection::TICKET_CREATED, [MessageHeaders::EVENT_AGGREGATE_ID => 'ticket-1', 'tenantId' => 'tenant-1']);

        self::assertEquals(1, $projection->initCallCount, 'Init should be called once for partitioned projection');
        self::assertCount(2, $projection->projectedEvents, 'All events should be processed for partitioned projection');
    }

    public function test_projection_with_partitioned_skip_mode(): void
    {
        $projection = new #[Projection('partitioned_skip_projection', partitionHeaderName: 'tenantId', automaticInitialization: false)] class {
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

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Cannot set partition header for projection partitioned_skip_projection with automatic initialization disabled');

        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );
    }

    public function test_it_throws_exception_when_no_licence(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Projections are part of Ecotone Enterprise. To use projections, please acquire an enterprise licence.');

        $projection = new #[Projection('test')] class {
            #[EventHandler('*')]
            public function handle(array $event): void
            {
            }
        };
        EcotoneLite::bootstrapFlowTesting(
            [$projection::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
                ->addExtensionObject(new InMemoryStreamSourceBuilder())
        );
    }

    public function test_it_with_event_handler_priority(): void
    {
        $db = [];
        $projectionA = new #[Projection('A')] class ($db) {
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
        $projectionB = new #[Projection('B')] class ($db) {
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
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->addExtensionObject($streamSource = new InMemoryStreamSourceBuilder())
        );

        $streamSource->append(
            Event::createWithType('no-priority', []),
        );

        $ecotone->publishEventWithRoutingKey('no-priority');
        self::assertEquals(['projectionA-no-priority', 'projectionB-no-priority'], $db);

        $db = [];
        $streamSource->append(
            Event::createWithType('with-priority', []),
        );
        $ecotone->publishEventWithRoutingKey('with-priority');
        self::assertEquals(['projectionB-with-priority', 'projectionA-with-priority'], $db);
    }
}
