<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\StandardRepository;

#[Repository]
class InMemoryStandardRepository implements StandardRepository
{
    /**
     * @var array
     */
    private $aggregates = [];

    /**
     * InMemoryAggregateRepository constructor.
     * @param array|Order[] $aggregates
     */
    private function __construct(array $aggregates)
    {
        foreach ($aggregates as $aggregate) {
            $this->save([], $aggregate, [], null);
        }
    }

    /**
     * @inheritDoc
     */
    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        $this->aggregates[get_class($aggregate)][array_pop($identifiers)] = $aggregate;
    }

    /**
     * @param array $aggregates
     *
     * @return InMemoryStandardRepository
     */
    public static function createWith(array $aggregates): self
    {
        return new static($aggregates);
    }

    /**
     * @return InMemoryStandardRepository
     */
    public static function createEmpty(): self
    {
        return new static([]);
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
    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        $aggregateId = $this->getAggregateId($identifiers);
        if (!$aggregateId || !isset($this->aggregates[$aggregateClassName][$aggregateId])) {
            return null;
        }

        return $this->aggregates[$aggregateClassName][$aggregateId];
    }

    /**
     * @param array $identifiers
     *
     * @return mixed|null
     */
    private function getAggregateId(array $identifiers)
    {
        return !empty($identifiers) ? array_shift($identifiers) : null;
    }
}