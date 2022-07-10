<?php


namespace Ecotone\EventSourcing;


class AggregateStreamMapping
{
    private array $aggregateToStreamMapping = [];

    private function __construct(array $aggregateToStreamMapping)
    {
        $this->aggregateToStreamMapping = $aggregateToStreamMapping;
    }

    public static function createEmpty() : static
    {
        return new self([]);
    }

    public static function createWith(array $aggregateToStreamMapping) : static
    {
        return new self($aggregateToStreamMapping);
    }

    public function getAggregateToStreamMapping(): array
    {
        return $this->aggregateToStreamMapping;
    }
}