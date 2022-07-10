<?php

namespace Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas;

use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;

#[\Ecotone\Modelling\Attribute\Repository]
class TwoSagasRepository extends InMemoryStandardRepository
{

}