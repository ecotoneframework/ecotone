<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;

#[Repository]
/**
 * licence Apache-2.0
 */
class LoggerRepository extends InMemoryEventSourcedRepository
{
}
