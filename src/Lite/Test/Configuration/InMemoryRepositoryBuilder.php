<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\InMemoryStandardRepository;
use Ecotone\Modelling\RepositoryBuilder;

final class InMemoryRepositoryBuilder implements RepositoryBuilder
{
    public function __construct(private array $aggregateClassNames, private bool $isEventSourced)
    {
    }

    public static function createForAllStateStoredAggregates(): self
    {
        return new self([], false);
    }

    public static function createForSetOfStateStoredAggregates(array $aggregateClassNames)
    {
        return new self($aggregateClassNames, false);
    }

    public static function createForAllEventSourcedAggregates(): self
    {
        return new self([], true);
    }

    public static function createForSetOfEventSourcedAggregates(array $aggregateClassNames)
    {
        return new self($aggregateClassNames, true);
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return isset($this->aggregateClassNames[$aggregateClassName]);
    }

    public function isEventSourced(): bool
    {
        return $this->isEventSourced;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return match ($this->isEventSourced) {
            true => new Definition(
                InMemoryEventSourcedRepository::class,
                [
                    [],
                    $this->aggregateClassNames,
                ]
            ),
            false => new Definition(
                InMemoryStandardRepository::class,
                [
                    [],
                    $this->aggregateClassNames,
                ]
            )
        };
    }
}
