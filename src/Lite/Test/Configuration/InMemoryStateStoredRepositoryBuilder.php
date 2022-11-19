<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\InMemoryStandardRepository;
use Ecotone\Modelling\RepositoryBuilder;
use Ecotone\Modelling\StandardRepository;

final class InMemoryStateStoredRepositoryBuilder implements RepositoryBuilder
{
    public function __construct(private InMemoryStandardRepository $inMemoryStandardRepository)
    {
    }

    public static function createForAllAggregates(): self
    {
        return new self(InMemoryStandardRepository::createEmpty());
    }

    public static function createForSetOfAggregates(array $aggregateClassNames)
    {
        return new self(new InMemoryStandardRepository([], $aggregateClassNames));
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return $this->inMemoryStandardRepository->canHandle($aggregateClassName);
    }

    public function isEventSourced(): bool
    {
        return false;
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): EventSourcedRepository|StandardRepository
    {
        return $this->inMemoryStandardRepository;
    }
}
