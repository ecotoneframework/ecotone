<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Modelling\Attribute\NotUniqueHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use stdClass;

class ServiceQueryHandlersWithAllowedNotUniqueClass
{
    #[QueryHandler(endpointId: 'execute1')]
    #[NotUniqueHandler]
    public function execute1(stdClass $class): int
    {
    }

    #[QueryHandler(endpointId: 'execute2')]
    #[NotUniqueHandler]
    public function execute2(stdClass $class): int
    {
    }
}
