<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes;

use Ecotone\Modelling\Annotation\Repository;
use Ecotone\Modelling\InMemoryStandardRepository;

#[Repository]
class BasketRepository extends InMemoryStandardRepository
{

}