<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;

/**
 * licence Apache-2.0
 */
class RepositoryStorage
{
    /**
     * @var array<EventSourcedRepository|StandardRepository|RepositoryBuilder> $repositories
     */
    private array $repositories;

    /**
     * @param array<EventSourcedRepository|StandardRepository> $aggregateRepositories
     */
    public function __construct(array $aggregateRepositories)
    {
        $this->repositories = array_values($aggregateRepositories);

        foreach ($this->repositories as $repository) {
            Assert::isTrue($repository instanceof EventSourcedRepository || $repository instanceof StandardRepository || $repository instanceof RepositoryBuilder, 'Invalid repository type provided. Expected EventSourcedRepository, StandardRepository. Got ' . get_class($repository) . '. Have you forgot to implement Interface?');
        }
    }

    public function getRepository(string $aggregateClassName, bool $isEventSourced): EventSourcedRepository|StandardRepository
    {
        if (count($this->repositories) === 1) {
            $repository = $this->repositories[0];
            if ($this->isEventSourced($repository) && ! $isEventSourced) {
                throw InvalidArgumentException::create("There is only one repository registered. For event sourcing usage, however aggregate {$aggregateClassName} is not event sourced. If it should be event sourced change attribute to " . EventSourcingAggregate::class);
            } elseif (! $this->isEventSourced($repository) && $isEventSourced) {
                throw InvalidArgumentException::create("There is only one repository registered. For standard aggregate usage, however aggregate {$aggregateClassName} is event sourced. If it should be standard change attribute to " . Aggregate::class);
            }

            return $this->returnRepository($repository, $aggregateClassName, $isEventSourced);
        }

        $eventSourcingRepositories = [];
        $standardRepositories = [];
        foreach ($this->repositories as $repository) {
            if ($this->isEventSourced($repository)) {
                $eventSourcingRepositories[] = $repository;
            } else {
                $standardRepositories[] = $repository;
            }
        }

        if ($isEventSourced && count($eventSourcingRepositories) === 1) {
            return $this->returnRepository($eventSourcingRepositories[0], $aggregateClassName, $isEventSourced);
        }
        if (! $isEventSourced && count($standardRepositories) === 1) {
            return $this->returnRepository($standardRepositories[0], $aggregateClassName, $isEventSourced);
        }

        foreach ($this->repositories as $repository) {
            if ($repository->canHandle($aggregateClassName)) {
                return $this->returnRepository($repository, $aggregateClassName, $isEventSourced);
            }
        }

        throw InvalidArgumentException::create('There is no repository available for aggregate: ' . $aggregateClassName . '. This happens because are multiple Repositories of given type registered, therefore each Repository need to specify which aggregate it can handle. If this fails during Ecotone Lite tests, consider turning off default In Memory implementations.');
    }

    private function returnRepository(EventSourcedRepository|StandardRepository|LazyRepositoryBuilder $repository, string $aggregateClassName, bool $isEventSourced): EventSourcedRepository|StandardRepository
    {
        // TODO: is RepositoryBuilder a public interface (regarding BC) ?
        // it does not make much sense with containers to have a lazy builder
        if ($repository instanceof LazyRepositoryBuilder) {
            $repository = $repository->build();
        }

        if ($isEventSourced) {
            Assert::isTrue($this->isEventSourced($repository), 'Registered standard repository for event sourced aggregate ' . $aggregateClassName);
        }
        if (! $isEventSourced) {
            Assert::isTrue(! $this->isEventSourced($repository), 'Registered event sourced repository for standard aggregate ' . $aggregateClassName);
        }

        return $repository;
    }

    private function isEventSourced(EventSourcedRepository|StandardRepository|RepositoryBuilder $repository): bool
    {
        if ($repository instanceof RepositoryBuilder) {
            return $repository->isEventSourced();
        }

        return $repository instanceof EventSourcedRepository;
    }
}
