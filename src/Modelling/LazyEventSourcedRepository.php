<?php

namespace Ecotone\Modelling;

/**
 * licence Apache-2.0
 */
class LazyEventSourcedRepository implements EventSourcedRepository
{
    private RepositoryStorage $repositoryStorage;

    private function __construct(RepositoryStorage $repositoryStorage)
    {
        $this->repositoryStorage = $repositoryStorage;
    }

    public static function create(array $aggregateRepositories): self
    {
        /** @phpstan-ignore-next-line */
        return new static(new RepositoryStorage($aggregateRepositories));
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return $this->repositoryStorage->getRepository($aggregateClassName, true)->canHandle($aggregateClassName);
    }

    public function findBy(string $aggregateClassName, array $identifiers): EventStream
    {
        return $this->repositoryStorage->getRepository($aggregateClassName, true)->findBy($aggregateClassName, $identifiers);
    }

    public function save(array $identifiers, string $aggregateClassName, array $events, array $metadata, int $versionBeforeHandling): void
    {
        $this->repositoryStorage->getRepository($aggregateClassName, true)->save($identifiers, $aggregateClassName, $events, $metadata, $versionBeforeHandling);
    }
}
