<?php

namespace Ecotone\Modelling\LazyEventBus;

use Ecotone\Messaging\Handler\NonProxyGateway;
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
     * @var NonProxyGateway|EventBus
     */
    private $eventBus;
    /**
     * @var InMemoryEventStore
     */
    private $inMemoryEventStore;

    /**
     * LazyEventBusInterceptor constructor.
     * @param NonProxyGateway $eventBus
     * @param InMemoryEventStore $inMemoryEventStore
     */
    public function __construct(NonProxyGateway $eventBus, InMemoryEventStore $inMemoryEventStore)
    {
        $this->eventBus = $eventBus;
        $this->inMemoryEventStore = $inMemoryEventStore;
    }

    public function publish(MethodInvocation $methodInvocation)
    {
        $reply = $methodInvocation->proceed();

        while (!$this->inMemoryEventStore->isEmpty()) {
            $event = $this->inMemoryEventStore->dequeue();

            $this->eventBus->execute([$event['data'], $event['metadata']]);
        }

        return $reply;
    }
}