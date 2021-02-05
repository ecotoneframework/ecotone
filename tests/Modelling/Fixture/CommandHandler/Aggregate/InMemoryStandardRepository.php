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
        $this->aggregates[$aggregate->getId()] = $aggregate;
    }

    /**
     * @param array $aggregates
     *
     * @return InMemoryStandardRepository
     */
    public static function createWith(array $aggregates): self
    {
        return new self($aggregates);
    }

    /**
     * @return InMemoryStandardRepository
     */
    public static function createEmpty(): self
    {
        return new self([]);
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
        if (!$aggregateId || !array_key_exists($aggregateId, $this->aggregates)) {
            return null;
        }

        return $this->aggregates[$aggregateId];
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