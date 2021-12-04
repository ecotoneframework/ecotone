<?php
declare(strict_types=1);


namespace Ecotone\Modelling;

use Ecotone\Modelling\Event;

/**
 * Class InMemoryEventSourcedRepository
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryEventSourcedRepository implements EventSourcedRepository
{
    private array $eventsPerAggregate;
    private array $aggregateTypes;

    public function __construct(array $eventsPerAggregate = [], array $aggregateTypes = [])
    {
        $this->eventsPerAggregate = $eventsPerAggregate;
        $this->aggregateTypes = $aggregateTypes;
    }

    public static function createEmpty() : self
    {
        /** @phpstan-ignore-next-line */
        return new static([], []);
    }

    public static function createWithExistingAggregate(array $identifiers, string $aggregateClassName, array $events) : self
    {
        $self = static::createEmpty();

        $self->save($identifiers, $aggregateClassName, $events, [], count($events));

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        return empty($this->aggregateTypes) ? true : in_array($aggregateClassName, $this->aggregateTypes);
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateClassName, array $identifiers): EventStream
    {
        $key = $this->getKey($identifiers);

        if (isset($this->eventsPerAggregate[$aggregateClassName][$key])) {
            $events = $this->eventsPerAggregate[$aggregateClassName][$key];

            return EventStream::createWith(count($events), array_map(fn(object $event) : Event => Event::create($event), $events));
        }

        return EventStream::createEmpty();
    }

    /**
     * @inheritDoc
     */
    public function save(array $identifiers, string $aggregateClassName, array $events, array $metadata, int $versionBeforeHandling): void
    {
        $key = $this->getKey($identifiers);

        if (!isset($this->eventsPerAggregate[$aggregateClassName][$key])) {
            $this->eventsPerAggregate[$aggregateClassName][$key] = $events;

            return;
        }

        $this->eventsPerAggregate[$aggregateClassName][$key] = array_merge($this->eventsPerAggregate[$aggregateClassName][$key], $events);
    }

    private function getKey(array $identifiers) : string
    {
        $key = "";
        foreach ($identifiers as $identifier) {
            $key .= (string)$identifier;
        }

        return $key;
    }
}