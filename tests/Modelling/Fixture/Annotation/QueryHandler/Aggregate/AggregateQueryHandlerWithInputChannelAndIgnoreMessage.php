<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * @Aggregate()
 */
class AggregateQueryHandlerWithInputChannelAndIgnoreMessage
{
    /**
     * @QueryHandler(inputChannelName="execute", endpointId="queryHandler", ignorePayload=true)
     */
    public function execute(\stdClass $class) : int
    {

    }
}