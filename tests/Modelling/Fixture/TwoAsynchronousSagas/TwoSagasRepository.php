<?php

namespace Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas;

use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStateStoredRepository;

#[\Ecotone\Api\Attribute\Repository]
/**
 * licence Apache-2.0
 */
class TwoSagasRepository extends InMemoryStateStoredRepository
{
}
