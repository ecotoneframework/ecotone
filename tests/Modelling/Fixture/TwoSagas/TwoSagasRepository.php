<?php

namespace Ecotone\Tests\Modelling\Fixture\TwoSagas;

use Ecotone\Tests\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;

#[\Ecotone\Modelling\Attribute\Repository]
class TwoSagasRepository extends InMemoryStandardRepository
{

}