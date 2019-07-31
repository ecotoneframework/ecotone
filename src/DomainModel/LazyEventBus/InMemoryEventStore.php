<?php


namespace Ecotone\DomainModel\LazyEventBus;

use SplQueue;

/**
 * Class InMemoryEventStore
 * @package Ecotone\DomainModel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryEventStore
{
    /**
     * @var SplQueue
     */
    private $eventsQueue;

    /**
     * InMemoryEventStore constructor.
     */
    public function __construct()
    {
        $this->eventsQueue = new SplQueue();
    }

    /**
     * @param object $message
     * @param array $metadata
     */
    public function enqueue(object $message, array $metadata): void
    {
        $this->eventsQueue->enqueue(
            [
                "data" => $message,
                "metadata" => $metadata
            ]
        );
    }

    public function isEmpty() : bool
    {
        return $this->eventsQueue->isEmpty();
    }

    /**
     * @return array|null
     */
    public function dequeue(): ?array
    {
        if ($this->eventsQueue->isEmpty()) {
            return null;
        }

        return $this->eventsQueue->dequeue();
    }
}