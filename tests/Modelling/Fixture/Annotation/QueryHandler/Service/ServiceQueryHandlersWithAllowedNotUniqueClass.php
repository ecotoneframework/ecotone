<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\NotUniqueHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class ServiceQueryHandlersWithAllowedNotUniqueClass
{
    #[QueryHandler(endpointId: "execute1")]
    #[NotUniqueHandler]
    public function execute1(\stdClass $class) : int
    {

    }

    #[QueryHandler(endpointId: "execute2")]
    #[NotUniqueHandler]
    public function execute2(\stdClass $class) : int
    {

    }
}