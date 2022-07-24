<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut\Infrastructure;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;

#[Repository]
class TwitterRepository extends InMemoryEventSourcedRepository
{
}
