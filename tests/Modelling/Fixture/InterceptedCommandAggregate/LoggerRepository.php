<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate;

use Ecotone\Modelling\Annotation\Repository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;

#[Repository]
class LoggerRepository extends InMemoryEventSourcedRepository
{

}