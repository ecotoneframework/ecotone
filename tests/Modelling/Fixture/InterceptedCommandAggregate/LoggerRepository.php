<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate;

use Ecotone\Api\Attribute\Repository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;

#[Repository]
/**
 * licence Apache-2.0
 */
class LoggerRepository extends InMemoryEventSourcedRepository
{
}
