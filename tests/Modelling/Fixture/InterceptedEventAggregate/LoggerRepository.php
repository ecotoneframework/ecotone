<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate;

use Ecotone\Modelling\Annotation\Repository;
use Ecotone\Modelling\InMemoryEventSourcedRepository;

#[Repository]
class LoggerRepository extends InMemoryEventSourcedRepository
{

}