<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Modelling\Event;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class InMemoryEventStoreRegistrationTest extends TestCase
{
    public function test_registering_in_memory_event_store_when_event_sourcing_configuration_is_in_memory(): void
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapForTesting(
            [TestEventConverter::class],
            [new TestEventConverter()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
                ->withEnvironment('test')
        );

        /** @var \Ecotone\EventSourcing\EventStore $eventStore */
        $eventStore = $ecotoneTestSupport->getGatewayByName(\Ecotone\EventSourcing\EventStore::class);

        $streamName = Uuid::uuid4()->toString();
        $eventStore->appendTo(
            $streamName,
            [
                Event::create(
                    $event = new TestEvent('test-data'),
                    [
                        'test_key' => 'test_value',
                    ]
                ),
            ]
        );

        $events = $eventStore->load($streamName);

        $this->assertCount(1, $events);
        $this->assertEquals($event, $events[0]->getPayload());
        $this->assertEquals('test_value', $events[0]->getMetadata()['test_key']);

        // Verify that the InMemoryEventStore is registered and working
        // The adapter should delegate to Ecotone's InMemoryEventStore
        $this->assertTrue($eventStore->hasStream($streamName));
    }
}

/**
 * licence Apache-2.0
 */
final class TestEvent
{
    public function __construct(public string $data)
    {
    }
}

/**
 * licence Apache-2.0
 */
final class TestEventConverter
{
    #[Converter]
    public function from(TestEvent $event): array
    {
        return ['data' => $event->data];
    }

    #[Converter]
    public function to(array $data): TestEvent
    {
        return new TestEvent($data['data']);
    }
}
