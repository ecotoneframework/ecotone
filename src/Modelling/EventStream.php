<?php


namespace Ecotone\Modelling;


class EventStream
{
    private int $aggregateVersion;
    /** @var array|null returns null if event stream was not found or events otherwise  */
    private ?iterable $events;

    private function __construct(int $aggregateVersion, ?iterable $events)
    {
        $this->aggregateVersion = $aggregateVersion;
        $this->events = $events;
    }

    public static function createWith(int $aggregateVersion, ?iterable $events) : static
    {
        return new static($aggregateVersion, $events);
    }

    public static function createEmpty() : static
    {
        return new static(0, null);
    }

    public function getAggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function getEvents(): ?iterable
    {
        return $this->events;
    }
}