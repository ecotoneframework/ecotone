<?php


namespace Ecotone\Modelling;


use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;

class RepositoryStorage
{
    private string $aggregateClassName;
    private ChannelResolver $channelResolver;
    private ReferenceSearchService $referenceSearchService;
    private array $aggregateRepositoryReferenceNames;
    private bool $isEventSourcedAggregate;

    public function __construct(string $aggregateClassName, bool $isEventSourcedAggregate, ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, array $aggregateRepositoryReferenceNames)
    {
        $this->aggregateClassName = $aggregateClassName;
        $this->isEventSourcedAggregate = $isEventSourcedAggregate;
        $this->referenceSearchService = $referenceSearchService;
        $this->aggregateRepositoryReferenceNames = array_values($aggregateRepositoryReferenceNames);
        $this->channelResolver = $channelResolver;
    }

    public function getRepository(): EventSourcedRepository|StandardRepository
    {
        if (count($this->aggregateRepositoryReferenceNames) === 1) {
            /** @var EventSourcedRepository|StandardRepository|RepositoryBuilder $repository */
            $repository = $this->referenceSearchService->get($this->aggregateRepositoryReferenceNames[0]);
            if ($this->isEventSourced($repository) && !$this->isEventSourcedAggregate) {
                throw InvalidArgumentException::create("There is only one repository registered. For event sourcing usage, however aggregate {$this->aggregateClassName} is not event sourced. If it should be event sourced change attribute to " . EventSourcingAggregate::class);
            } else if (!$this->isEventSourced($repository) && $this->isEventSourcedAggregate) {
                throw InvalidArgumentException::create("There is only one repository registered. For standard aggregate usage, however aggregate {$this->aggregateClassName} is event sourced. If it should be standard change attribute to " . Aggregate::class);
            }

            return $this->returnRepository($repository);
        }

        if (count($this->aggregateRepositoryReferenceNames) === 2) {
            $repositoryOne = $this->referenceSearchService->get($this->aggregateRepositoryReferenceNames[0]);
            $repositoryTwo = $this->referenceSearchService->get($this->aggregateRepositoryReferenceNames[1]);

            $repositoryOneIsEventSourced = $this->isEventSourced($repositoryOne);
            $repositoryTwoIsEventSourced = $this->isEventSourced($repositoryTwo);

            if (
                ($repositoryOneIsEventSourced && !$repositoryTwoIsEventSourced)
                ||
                (!$repositoryOneIsEventSourced && $repositoryTwoIsEventSourced)
            ) {
                if ($this->isEventSourcedAggregate) {
                    return $this->returnRepository($repositoryOneIsEventSourced ? $repositoryOne : $repositoryTwo);
                }

                return $this->returnRepository($repositoryOneIsEventSourced ? $repositoryTwo : $repositoryOne);
            }
        }

        foreach ($this->aggregateRepositoryReferenceNames as $aggregateRepositoryReferenceName) {
            /** @var EventSourcedRepository|StandardRepository|RepositoryBuilder $repository */
            $repository = $this->referenceSearchService->get($aggregateRepositoryReferenceName);

            if ($repository->canHandle($this->aggregateClassName)) {
                return $this->returnRepository($repository);
            }
        }

        throw InvalidArgumentException::create("There is no repository available for aggregate: " . $this->aggregateClassName);
    }

    private function returnRepository(EventSourcedRepository|StandardRepository|RepositoryBuilder $repository) : EventSourcedRepository|StandardRepository
    {
        if ($repository instanceof RepositoryBuilder) {
            $repository = $repository->build($this->channelResolver, $this->referenceSearchService);
        }

        if ($this->isEventSourcedAggregate) {
            Assert::isTrue($this->isEventSourced($repository), "Registered standard repository for event sourced aggregate " . $this->aggregateClassName);
        }
        if (!$this->isEventSourcedAggregate) {
            Assert::isTrue(!$this->isEventSourced($repository), "Registered event sourced repository for standard aggregate " . $this->aggregateClassName);
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