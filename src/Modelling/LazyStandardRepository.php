<?php

namespace Ecotone\Modelling;

/**
 * licence Apache-2.0
 */
class LazyStandardRepository implements StandardRepository
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
        return $this->repositoryStorage->getRepository($aggregateClassName, false)->canHandle($aggregateClassName);
    }

    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        return $this->repositoryStorage->getRepository($aggregateClassName, false)->findBy($aggregateClassName, $identifiers);
    }

    public function save(array $identifiers, object $aggregate, array $metadata, ?int $versionBeforeHandling): void
    {
        $this->repositoryStorage->getRepository(get_class($aggregate), false)->save($identifiers, $aggregate, $metadata, $versionBeforeHandling);
    }
}
