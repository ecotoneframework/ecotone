<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Repository;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\Messaging\ConcurrencyException;
use Ecotone\Api\Modelling\StateStoredRepository;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
#[Repository]
class InMemoryStateStoredRepository implements StateStoredRepository
{
    /**
     * @var object[]
     */
    private array $aggregates;
    private ?array $aggregateTypes;
    /**
     * @var array<string, array<string, int>>
     */
    private array $versions = [];

    public function __construct(array $aggregates = [], ?array $aggregateTypes = [])
    {
        $this->aggregates = $aggregates;
        $this->aggregateTypes = $aggregateTypes;
    }


    public static function createEmpty(): self
    {
        /** @phpstan-ignore-next-line */
        return new static([], []);
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
        return $classDefinition->hasClassAnnotationOfPreciseType(Type::attribute(Aggregate::class)) || $classDefinition->hasClassAnnotationOfPreciseType(Type::attribute(Saga::class));
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        $key = $this->getKey($identifiers);

        if (isset($this->aggregates[$aggregateClassName][$key])) {
            return $this->aggregates[$aggregateClassName][$key];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        $key = $this->getKey($identifiers);
        $currentVersion = $this->versions[$aggregate::class][$key] ?? 0;
        if ($expectedVersion !== null && $expectedVersion !== $currentVersion) {
            throw ConcurrencyException::forStaleAggregate($aggregate::class, AggregateIdString::from($identifiers), $expectedVersion, $currentVersion);
        }

        $this->aggregates[$aggregate::class][$key] = $aggregate;
        $this->versions[$aggregate::class][$key] = $currentVersion + 1;
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
