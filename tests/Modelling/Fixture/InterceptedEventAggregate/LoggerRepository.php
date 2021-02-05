<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;

#[Repository]
class LoggerRepository extends InMemoryEventSourcedRepository
{

}