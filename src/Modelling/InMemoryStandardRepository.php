<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\Attribute\Saga;

/**
 * licence Apache-2.0
 */
#[Repository]
class InMemoryStandardRepository implements StandardRepository
{
    /**
     * @var object[]
     */
    private array $aggregates;
    private ?array $aggregateTypes;

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

        $this->aggregates[get_class($aggregate)][$key] = $aggregate;
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
