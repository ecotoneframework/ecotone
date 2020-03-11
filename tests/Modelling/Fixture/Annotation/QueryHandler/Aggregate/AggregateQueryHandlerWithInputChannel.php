<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * @Aggregate()
 */
class AggregateQueryHandlerWithInputChannel
{
    /**
     * @QueryHandler(inputChannelName="execute", endpointId="queryHandler")
     */
    public function execute() : int
    {

    }
}