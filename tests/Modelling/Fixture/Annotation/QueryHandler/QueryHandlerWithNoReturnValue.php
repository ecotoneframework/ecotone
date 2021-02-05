<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class QueryHandlerWithNoReturnValue
{
    #[QueryHandler]
    public function searchFor(SomeQuery $query): void
    {
    }
}