<?php

namespace Test\Ecotone\Modelling\Fixture\SimplifiedAggregate;

use Ecotone\Api\Attribute\Repository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStateStoredRepository;

#[Repository]
/**
 * licence Apache-2.0
 */
class SimplifiedAggregateRepository extends InMemoryStateStoredRepository
{
}
