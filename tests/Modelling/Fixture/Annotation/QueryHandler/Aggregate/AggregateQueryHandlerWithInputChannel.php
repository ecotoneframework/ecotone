<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\QueryHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateQueryHandlerWithInputChannel
{
    #[QueryHandler('execute', 'queryHandler')]
    public function execute(): int
    {
    }
}
