<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Api\EventSourcing\EventStore;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Modelling\EventStoreEventSourcedRepository;
use Ecotone\Modelling\InMemoryStateStoredRepository;
use Ecotone\Modelling\RepositoryBuilder;

/**
 * licence Apache-2.0
 */
final class InMemoryRepositoryBuilder implements RepositoryBuilder
{
    public function __construct(private ?array $aggregateClassNames, private bool $isEventSourced)
    {
    }

    public static function createForAllStateStoredAggregates(): self
    {
        return new self([], false);
    }

    public static function createDefaultStateStoredRepository(): self
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

    public static function createDefaultEventSourcedRepository()
    {
        return new self([], true);
    }

    public function canHandle(string $aggregateClassName): bool
    {
        if ($this->aggregateClassNames === null) {
            return false;
        }

        return isset($this->aggregateClassNames[$aggregateClassName]);
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return match ($this->isEventSourced) {
            true => new Definition(
                EventStoreEventSourcedRepository::class,
                [
                    new Reference(EventStore::RAW_REFERENCE),
                    $this->aggregateClassNames,
                ]
            ),
            false => new Definition(
                InMemoryStateStoredRepository::class,
                [
                    [],
                    $this->aggregateClassNames,
                ]
            )
        };
    }
}
