<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\QueryHandler;

class ServiceQueryHandlerWithClass
{
    #[QueryHandler(endpointId: "queryHandler")]
    public function execute(\stdClass $class) : int
    {

    }
}