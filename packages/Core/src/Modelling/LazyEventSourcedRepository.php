<?php


namespace Ecotone\Modelling;


use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;

class LazyEventSourcedRepository implements EventSourcedRepository
{
    private RepositoryStorage $repositoryStorage;

    private function __construct(RepositoryStorage $repositoryStorage)
    {
        $this->repositoryStorage = $repositoryStorage;
    }

    public static function create(string $aggregateClassName, bool $isEventSourcedAggregate, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, array $aggregateRepositoryReferenceNames) : self
    {
        /** @phpstan-ignore-next-line */
        return new static(new RepositoryStorage($aggregateClassName, $isEventSourcedAggregate, $channelResolver, $referenceSearchService, $aggregateRepositoryReferenceNames));
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return $this->repositoryStorage->getRepository()->canHandle($aggregateClassName);
    }

    public function findBy(string $aggregateClassName, array $identifiers): EventStream
    {
        return $this->repositoryStorage->getRepository()->findBy($aggregateClassName, $identifiers);
    }

    public function save(array $identifiers, string $aggregateClassName, array $events, array $metadata, int $versionBeforeHandling): void
    {
        $this->repositoryStorage->getRepository()->save($identifiers, $aggregateClassName, $events, $metadata, $versionBeforeHandling);
    }
}