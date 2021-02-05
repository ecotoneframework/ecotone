<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;

#[Repository]
class JobRepository extends InMemoryEventSourcedRepository
{
    public function __construct()
    {
        parent::__construct([], [Job::class]);
    }
}