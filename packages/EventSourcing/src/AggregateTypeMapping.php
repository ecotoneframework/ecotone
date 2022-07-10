<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing;

final class AggregateTypeMapping
{
    private function __construct(private array $aggregateTypeMapping)
    {
    }

    public static function createEmpty() : static
    {
        return new self([]);
    }

    public static function createWith(array $aggregateTypeMapping) : static
    {
        return new self($aggregateTypeMapping);
    }

    public function getAggregateTypeMapping(): array
    {
        return $this->aggregateTypeMapping;
    }
}
