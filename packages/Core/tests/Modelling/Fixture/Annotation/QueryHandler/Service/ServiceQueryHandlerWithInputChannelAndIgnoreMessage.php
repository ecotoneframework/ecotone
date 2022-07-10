<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\IgnorePayload;
use Ecotone\Modelling\Attribute\QueryHandler;

class ServiceQueryHandlerWithInputChannelAndIgnoreMessage
{
    #[QueryHandler("execute", "queryHandler")]
    #[IgnorePayload]
    public function execute(\stdClass $class) : int
    {

    }
}