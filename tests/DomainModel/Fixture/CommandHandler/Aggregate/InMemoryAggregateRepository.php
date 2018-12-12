<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate;
use SimplyCodedSoftware\DomainModel\AggregateNotFoundException;
use SimplyCodedSoftware\DomainModel\AggregateRepository;
use SimplyCodedSoftware\DomainModel\AggregateVersionMismatchException;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class InMemoryAggregateRepository
 * @package SimplyCodedSoftware\DomainModel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryAggregateRepository implements AggregateRepository
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
            $this->save($aggregate);
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
    public function findBy(array $identifiers)
    {
        $aggregateId = $this->getAggregateId($identifiers);
        if (!$aggregateId || !array_key_exists($aggregateId, $this->aggregates)) {
            throw AggregateNotFoundException::create("Aggregate with id {$aggregateId} was not found");
        }

        return $this->aggregates[$aggregateId];
    }

    /**
     * @inheritDoc
     */
    public function findWithLockingBy(array $identifiers, int $expectedVersion)
    {
        /** @var VersionAggregate $aggregate */
        $aggregate =  $this->findBy($identifiers);

        if ($expectedVersion != $aggregate->getVersion()) {
            throw AggregateVersionMismatchException::create("Expected aggregate version {$expectedVersion} got {$aggregate->getVersion()}");
        }

        return $aggregate;
    }

    /**
     * @inheritDoc
     */
    public function save($aggregate): void
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