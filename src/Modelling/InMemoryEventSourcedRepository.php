<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingSaga;
use Ecotone\Modelling\Attribute\Repository;

/**
 * Class InMemoryEventSourcedRepository
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
#[Repository]
class InMemoryEventSourcedRepository implements EventSourcedRepository
{
    /**
     * @var array<string, array<string, Event[]>>
     */
    private array $eventsPerAggregate;
    private ?array $aggregateTypes;

    public function __construct(array $eventsPerAggregate = [], ?array $aggregateTypes = [])
    {
        $this->eventsPerAggregate = $eventsPerAggregate;
        $this->aggregateTypes = $aggregateTypes;
    }

    public static function createEmpty(): self
    {
        /** @phpstan-ignore-next-line */
        return new static([], []);
    }

    public static function createWithExistingAggregate(array $identifiers, string $aggregateClassName, array $events): self
    {
        $self = static::createEmpty();

        $events = array_map(static fn ($event) => Event::create($event), $events);

        $self->save($identifiers, $aggregateClassName, $events, [], count($events));

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        if ($this->aggregateTypes === null) {
            return false;
        }

        if (in_array($aggregateClassName, $this->aggregateTypes)) {
            return true;
        }

        $classDefinition = ClassDefinition::createFor(Type::object($aggregateClassName));
        return $classDefinition->hasClassAnnotationOfPreciseType(Type::attribute(EventSourcingAggregate::class)) || $classDefinition->hasClassAnnotationOfPreciseType(Type::attribute(EventSourcingSaga::class));
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateClassName, array $identifiers, int $fromAggregateVersion = 1): EventStream
    {
        $key = $this->getKey($identifiers);

        if (isset($this->eventsPerAggregate[$aggregateClassName][$key])) {
            $events = $this->eventsPerAggregate[$aggregateClassName][$key];

            if ($fromAggregateVersion > 1) {
                $events = array_slice($events, $fromAggregateVersion - 1);
            }

            return EventStream::createWith(count($events), $events);
        }

        return EventStream::createEmpty();
    }

    /**
     * @inheritDoc
     */
    public function save(array $identifiers, string $aggregateClassName, array $events, array $metadata, int $versionBeforeHandling): void
    {
        $key = $this->getKey($identifiers);

        if (! isset($this->eventsPerAggregate[$aggregateClassName][$key])) {
            $this->eventsPerAggregate[$aggregateClassName][$key] = $events;

            return;
        }

        $this->eventsPerAggregate[$aggregateClassName][$key] = array_merge($this->eventsPerAggregate[$aggregateClassName][$key], $events);
    }

    private function getKey(array $identifiers): string
    {
        $key = '';
        foreach ($identifiers as $identifier) {
            $key .= (string)$identifier;
        }

        return $key;
    }
}
