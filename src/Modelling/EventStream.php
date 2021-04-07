<?php


namespace Ecotone\Modelling;


use Ecotone\Modelling\Event;
use Ecotone\Messaging\Support\Assert;

class EventStream
{
    private int $aggregateVersion;
    /** @var Event[]  */
    private array $events;

    private function __construct(int $aggregateVersion, array $events)
    {
        Assert::allInstanceOfType($events, Event::class);

        $this->aggregateVersion = $aggregateVersion;
        $this->events = $events;
    }

    /**
     * @param int $aggregateVersion
     * @param Event[] $events
     * @return static
     */
    public static function createWith(int $aggregateVersion, array $events) : static
    {
        return new static($aggregateVersion, $events);
    }

    public static function createEmpty() : static
    {
        return new static(0, []);
    }

    public function getAggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    /**
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}