<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * @MessageEndpoint()
 */
class ServiceQueryHandlerWithInputChannelAndIgnoreMessage
{
    /**
     * @QueryHandler(inputChannelName="execute", endpointId="queryHandler", ignorePayload=true)
     */
    public function execute(\stdClass $class) : int
    {

    }
}