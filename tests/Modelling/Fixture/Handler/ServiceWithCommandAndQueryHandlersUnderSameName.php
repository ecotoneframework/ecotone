<?php


namespace Test\Ecotone\Modelling\Fixture\Handler;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class ServiceWithCommandAndQueryHandlersUnderSameName
{
    #[QueryHandler(routingKey: "action")]
    public function execute1() : int
    {

    }

    #[CommandHandler(routingKey: "action")]
    public function execute2() : int
    {

    }
}