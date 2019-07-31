<?php
declare(strict_types=1);


namespace Test\Ecotone\DomainModel\Unit\LazyEventBus;

use PHPUnit\Framework\TestCase;
use Ecotone\DomainModel\EventBus;
use Ecotone\DomainModel\LazyEventBus\InMemoryEventStore;
use Ecotone\DomainModel\LazyEventBus\LazyEventBusInterceptor;

/**
 * Class LazyEventBusInterceptorTest
 * @package Test\Ecotone\DomainModel\Unit\LazyEventBus
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyEventBusInterceptorTest extends TestCase
{
    public function test_publishing_to_event_bus_when_event_is_enqueued()
    {
        $payload = new \stdClass();
        $metadata = ["token" => 123];

        $eventBus = $this->createMock(EventBus::class);

        $eventBus
            ->expects($this->once())
            ->method("sendWithMetadata")
            ->with($payload, $metadata);

        $inMemoryEventStore = new InMemoryEventStore();
        $inMemoryEventStore->enqueue($payload, $metadata);
        $lazyEventBus = new LazyEventBusInterceptor($eventBus, $inMemoryEventStore);

        $lazyEventBus->publish();
        $lazyEventBus->publish();
    }
}