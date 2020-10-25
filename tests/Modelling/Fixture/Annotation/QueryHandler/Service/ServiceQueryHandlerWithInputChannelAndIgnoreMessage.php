<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\IgnorePayload;
use Ecotone\Modelling\Annotation\QueryHandler;

class ServiceQueryHandlerWithInputChannelAndIgnoreMessage
{
    #[QueryHandler("execute", "queryHandler")]
    #[IgnorePayload]
    public function execute(\stdClass $class) : int
    {

    }
}