<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\QueryHandler;

#[Aggregate]
class AggregateQueryHandlerWithInputChannel
{
    #[QueryHandler("execute", "queryHandler")]
    public function execute() : int
    {

    }
}