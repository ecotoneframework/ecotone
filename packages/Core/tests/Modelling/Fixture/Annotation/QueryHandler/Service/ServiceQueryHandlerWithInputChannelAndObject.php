<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\QueryHandler;

class ServiceQueryHandlerWithInputChannelAndObject
{
    #[QueryHandler("execute", "queryHandler")]
    public function execute(\stdClass $class) : int
    {

    }
}