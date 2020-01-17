<?php
declare(strict_types=1);


namespace Test\Ecotone\Modelling\Unit\LazyEventBus;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\LazyEventBus\InMemoryEventStore;
use Ecotone\Modelling\LazyEventBus\LazyEventBusInterceptor;
use PHPUnit\Framework\TestCase;

/**
 * Class LazyEventBusInterceptorTest
 * @package Test\Ecotone\Modelling\Unit\LazyEventBus
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

        $methodInvocation = $this->createMock(MethodInvocation::class);

        $lazyEventBus->publish($methodInvocation);
        $lazyEventBus->publish($methodInvocation);
    }
}