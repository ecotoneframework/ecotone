<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\QueryHandler;
use stdClass;

#[Aggregate]
class AggregateQueryHandlerWithClass
{
    #[QueryHandler(endpointId: 'queryHandler')]
    public function execute(stdClass $class): int
    {
    }
}
