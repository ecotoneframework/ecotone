<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;
use Ecotone\Modelling\AggregateRepository;
use Ecotone\Modelling\AggregateVersionMismatchException;

/**
 * Class InMemoryAggregateRepository
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @\Ecotone\Modelling\Annotation\AggregateRepository()
 */
class InMemoryAggregateRepository implements AggregateRepository
{
    /**
     * @var array
     */
    private $aggregates = [];

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        return true;
    }

    /**
     * InMemoryAggregateRepository constructor.
     * @param array|Order[] $aggregates
     */
    private function __construct(array $aggregates)
    {
        foreach ($aggregates as $aggregate) {
            $this->save([], $aggregate, []);
        }
    }

    /**
     * @param array $aggregates
     *
     * @return InMemoryAggregateRepository
     */
    public static function createWith(array $aggregates) : self
    {
        return new self($aggregates);
    }

    /**
     * @return InMemoryAggregateRepository
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateClassName, array $identifiers)
    {
        $aggregateId = $this->getAggregateId($identifiers);
        if (!$aggregateId || !array_key_exists($aggregateId, $this->aggregates)) {
            return null;
        }

        return $this->aggregates[$aggregateId];
    }

    /**
     * @inheritDoc
     */
    public function findWithLockingBy(string $aggregateClassName, array $identifiers, int $expectedVersion)
    {
        /** @var VersionAggregate $aggregate */
        $aggregate =  $this->findBy($aggregateClassName, $identifiers);

        if ($expectedVersion != $aggregate->getVersion()) {
            throw AggregateVersionMismatchException::create("Expected aggregate version {$expectedVersion} got {$aggregate->getVersion()}");
        }

        return $aggregate;
    }

    /**
     * @inheritDoc
     */
    public function save(array $identifiers, $aggregate, array $metadata): void
    {
        $this->aggregates[$aggregate->getId()] = $aggregate;
    }

    /**
     * @param array $identifiers
     *
     * @return mixed|null
     */
    private function getAggregateId(array $identifiers)
    {
        $aggregateId = !empty($identifiers) ? array_shift($identifiers) : null;

        return $aggregateId;
    }
}