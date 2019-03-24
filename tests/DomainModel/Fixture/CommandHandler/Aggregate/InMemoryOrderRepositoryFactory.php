<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate;
use SimplyCodedSoftware\DomainModel\AggregateRepository;
use SimplyCodedSoftware\DomainModel\AggregateRepositoryFactory;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class InMemoryOrderRepositoryFactory
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryOrderRepositoryFactory implements AggregateRepositoryFactory
{
    /**
     * @var InMemoryAggregateRepository
     */
    private $orderRepository;

    /**
     * InMemoryOrderRepositoryFactory constructor.
     * @param Order[] $orders
     */
    public function __construct(array $orders)
    {
        $this->orderRepository = InMemoryAggregateRepository::createWith($orders);
    }

    /**
     * @param array $orders
     * @return InMemoryOrderRepositoryFactory
     */
    public static function createWith(array $orders) : self
    {
        return new self($orders);
    }

    /**
     * @return InMemoryOrderRepositoryFactory
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function canHandle(ReferenceSearchService $referenceSearchService, string $aggregateClassName): bool
    {
        return $aggregateClassName === Order::class;
    }

    /**
     * @inheritDoc
     */
    public function getFor(ReferenceSearchService $referenceSearchService, string $aggregateClassName): AggregateRepository
    {
        return $this->orderRepository;
    }
}