<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventSourcing\CustomRepository;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder\EventSourcingAggregateWithInternalRecorder;

#[Repository]
final class CustomEventSourcingRepository extends InMemoryEventSourcedRepository implements EventSourcedRepository
{
    public function canHandle(string $aggregateClassName): bool
    {
        return EventSourcingAggregateWithInternalRecorder::class === $aggregateClassName;
    }
}
