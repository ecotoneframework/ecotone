<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\QueryHandler;

class ServiceQueryHandlersWithNotUniqueClass
{
    #[QueryHandler]
    public function execute1(\stdClass $class) : int
    {

    }

    #[QueryHandler]
    public function execute2(\stdClass $class) : int
    {

    }
}