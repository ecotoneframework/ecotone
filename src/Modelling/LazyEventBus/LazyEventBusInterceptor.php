<?php

namespace Ecotone\Modelling\LazyEventBus;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Modelling\EventBus;

/**
 * Class LazyEventBusInterceptor
 * @package Ecotone\Modelling\LazyEventBus
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyEventBusInterceptor
{
    const PRECEDENCE = 10;

    /**
     * @var EventBus
     */
    private $eventBus;
    /**
     * @var InMemoryEventStore
     */
    private $inMemoryEventStore;

    /**
     * LazyEventBusInterceptor constructor.
     * @param EventBus $eventBus
     * @param InMemoryEventStore $inMemoryEventStore
     */
    public function __construct(EventBus $eventBus, InMemoryEventStore $inMemoryEventStore)
    {
        $this->eventBus = $eventBus;
        $this->inMemoryEventStore = $inMemoryEventStore;
    }

    public function publish(MethodInvocation $methodInvocation)
    {
        $reply = $methodInvocation->proceed();

        while (!$this->inMemoryEventStore->isEmpty()) {
            $event = $this->inMemoryEventStore->dequeue();

            $this->eventBus->sendWithMetadata($event['data'], $event['metadata']);
        }

        return $reply;
    }
}