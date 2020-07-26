<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\QueryHandler;

class ServiceQueryHandlerWithInputChannel
{
    /**
     * @QueryHandler(inputChannelName="execute", endpointId="queryHandler")
     */
    public function execute() : int
    {

    }
}