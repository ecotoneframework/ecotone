<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\QueryHandler;

#[Aggregate]
class QueryHandlerWithNoReturnValue
{
    #[QueryHandler]
    public function searchFor(SomeQuery $query): void
    {
    }
}