<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite;

use Ecotone\EventSourcing\Attribute\FromStream;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Event;
use Ecotone\Projecting\Attribute;
use Ecotone\Projecting\Attribute\Polling;
use Ecotone\Projecting\Attribute\ProjectionV2;
use Ecotone\Projecting\StreamPage;
use Ecotone\Projecting\StreamSource;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class InMemoryEventStoreRegistrationTest extends TestCase
{
    public function test_registers_in_memory_event_store_stream_source_when_pdo_event_sourcing_is_disabled(): void
    {
        $testEvent = new class () {
            public function __construct(public int $id = 0, public string $name = '')
            {
            }
        };

        $projection = new #[ProjectionV2('test_projection'), Polling('test_projection_poller'), FromStream('test_stream')] class ($testEvent) {
            public array $events = [];
            public int $callCount = 0;
            private string $eventClass;

            public function __construct(object $testEvent)
            {
                $this->eventClass = $testEvent::class;
            }

            #[EventHandler]
            public function onEvent(object $event): void
            {
                $this->callCount++;
                $this->events[] = ['id' => $event->id, 'name' => $event->name];
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$projection::class, $testEvent::class],
            [$projection],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        // And adding events to event store using withEventStream
        $ecotone->withEventStream('test_stream', [
            Event::create(new ($testEvent::class)(1, 'Event 1')),
            Event::create(new ($testEvent::class)(2, 'Event 2')),
        ]);

        // When running the polling projection (it reads from the stream source)
        $ecotone->run('test_projection_poller', ExecutionPollingMetadata::createWithTestingSetup());

        // Then the projection should have consumed events from InMemoryEventStore
        $this->assertEquals(2, $projection->callCount, 'Event handler should have been called 2 times');
        $this->assertCount(2, $projection->events, 'Projection should have consumed 2 events');
        $this->assertEquals(['id' => 1, 'name' => 'Event 1'], $projection->events[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Event 2'], $projection->events[1]);
    }

    public function test_custom_userland_stream_source_is_used_when_provided(): void
    {
        $testEvent = new class () {
            public function __construct(public int $id = 0, public string $name = '')
            {
            }
        };

        $projection = new #[ProjectionV2('test_projection'), Polling('test_projection_poller'), FromStream('test_stream')] class {
            public array $events = [];
            public int $callCount = 0;

            #[EventHandler]
            public function onEvent(object $event): void
            {
                $this->callCount++;
                $this->events[] = ['id' => $event->id, 'name' => $event->name];
            }
        };

        $customStreamSource = new #[Attribute\StreamSource] class () implements StreamSource {
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
            [$projection::class, $testEvent::class, $customStreamSource::class],
            [$projection, $customStreamSource],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        $customStreamSource->append(
            Event::create(new ($testEvent::class)(1, 'Event 1')),
            Event::create(new ($testEvent::class)(2, 'Event 2')),
        );

        $ecotone->run('test_projection_poller', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertEquals(2, $projection->callCount, 'Event handler should have been called 2 times');
        $this->assertCount(2, $projection->events, 'Projection should have consumed 2 events');
        $this->assertEquals(['id' => 1, 'name' => 'Event 1'], $projection->events[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Event 2'], $projection->events[1]);
    }
}

/**
 * licence Apache-2.0
 */
final class TestEventForInMemoryMode
{
    public function __construct(public int $id, public string $name)
    {
    }
}

/**
 * licence Apache-2.0
 */
final class TestEventForInMemoryModeConverter
{
    #[Converter]
    public function from(TestEventForInMemoryMode $event): array
    {
        return ['id' => $event->id, 'name' => $event->name];
    }

    #[Converter]
    public function to(array $data): TestEventForInMemoryMode
    {
        return new TestEventForInMemoryMode($data['id'], $data['name']);
    }
}
