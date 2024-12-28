<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder;

use Ecotone\Modelling\Attribute\Repository;

interface JobRepositoryInterface
{
    #[Repository]
    public function findBy(string $id): ?Job;

    #[Repository]
    public function save(Job $job): void;
}
