<?php

namespace Tests\Ecotone\Modelling\Fixture\TwoAsynchronousSagas;

use Tests\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;

#[\Ecotone\Modelling\Attribute\Repository]
class TwoSagasRepository extends InMemoryStandardRepository
{

}