<?php


namespace SimplyCodedSoftware\DomainModel\LazyEventBus;

use SimplyCodedSoftware\DomainModel\EventBus;

/**
 * Class LazyEventBusInterceptor
 * @package SimplyCodedSoftware\DomainModel\LazyEventBus
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyEventBusInterceptor
{
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

    public function publish(): void
    {
        while (!$this->inMemoryEventStore->isEmpty()) {
            $event = $this->inMemoryEventStore->dequeue();

            $this->eventBus->sendWithMetadata($event['data'], $event['metadata']);
        }
    }
}