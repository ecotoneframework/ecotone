<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\QueryHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class QueryHandlerWithNoReturnValue
{
    #[QueryHandler]
    public function searchFor(SomeQuery $query): void
    {
    }
}
