<?php


namespace Ecotone\Tests\Modelling\Fixture\InterceptedCommandAggregate;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;

#[Repository]
class LoggerRepository extends InMemoryEventSourcedRepository
{

}