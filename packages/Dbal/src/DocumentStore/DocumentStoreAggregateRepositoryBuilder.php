<?php

namespace Ecotone\Dbal\DocumentStore;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\RepositoryBuilder;
use Ecotone\Modelling\StandardRepository;

final class DocumentStoreAggregateRepositoryBuilder implements RepositoryBuilder
{
    public function __construct(private string $documentStoreReferenceName)
    {
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return true;
    }

    public function isEventSourced(): bool
    {
        return false;
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): EventSourcedRepository|StandardRepository
    {
        return new DocumentStoreAggregateRepository($referenceSearchService->get($this->documentStoreReferenceName));
    }
}
