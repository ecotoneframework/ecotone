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
        $projection = new #[Projection(self::NAME)] class {
            public const NAME = 'projection_with_lifecycle';
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
