<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\InMemoryStandardRepository;
use Ecotone\Modelling\RepositoryBuilder;
use Ecotone\Modelling\StandardRepository;

final class InMemoryRepositoryBuilder implements RepositoryBuilder
{
    public function __construct(private object $inMemoryRepository, private bool $isEventSourced)
    {
    }

    public static function createForAllStateStoredAggregates(): self
    {
        return new self(InMemoryStandardRepository::createEmpty(), false);
    }

    public static function createForSetOfStateStoredAggregates(array $aggregateClassNames)
    {
        return new self(new InMemoryStandardRepository([], $aggregateClassNames), false);
    }

    public static function createForAllEventSourcedAggregates(): self
    {
        return new self(InMemoryEventSourcedRepository::createEmpty(), true);
    }

    public static function createForSetOfEventSourcedAggregates(array $aggregateClassNames)
    {
        return new self(new InMemoryEventSourcedRepository([], $aggregateClassNames), true);
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return $this->inMemoryRepository->canHandle($aggregateClassName);
    }

    public function isEventSourced(): bool
    {
        return $this->isEventSourced;
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): EventSourcedRepository|StandardRepository
    {
        return $this->inMemoryRepository;
    }

    public function getRepository(): EventSourcedRepository|StandardRepository
    {
        return $this->inMemoryRepository;
    }
}
