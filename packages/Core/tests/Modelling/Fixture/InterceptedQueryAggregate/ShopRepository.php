<?php
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate;

use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\InMemoryStandardRepository;

#[Repository]
class ShopRepository extends InMemoryStandardRepository
{

}