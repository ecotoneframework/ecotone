<?php
declare(strict_types=1);


namespace Ecotone\Modelling;

/**
 * Class InMemoryEventSourcedRepository
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryEventSourcedRepository implements EventSourcedRepository
{
    /**
     * @var array
     */
    private $eventsPerAggregate;

    public function __construct(array $eventsPerAggregate = [])
    {
        $this->eventsPerAggregate = $eventsPerAggregate;
    }

    public static function createEmpty() : self
    {
        return new static([]);
    }

    public static function createWithExistingAggregate(array $identifiers, array $events) : self
    {
        $self = static::createEmpty();

        $self->save($identifiers, $events, [], null);

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateClassName, array $identifiers): ?array
    {
        $key = $this->getKey($identifiers);

        if (isset($this->eventsPerAggregate[$key])) {
            return $this->eventsPerAggregate[$key];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function save(array $identifiers, array $events, array $metadata, ?int $expectedVersion): void
    {
        $key = $this->getKey($identifiers);

        if (!isset($this->eventsPerAggregate[$key])) {
            $this->eventsPerAggregate[$key] = $events;

            return;
        }

        $this->eventsPerAggregate[$key] = array_merge($this->eventsPerAggregate[$key], $events);
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