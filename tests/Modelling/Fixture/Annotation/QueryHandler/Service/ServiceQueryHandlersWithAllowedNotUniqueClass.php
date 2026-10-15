<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Api\Attribute\NotUniqueHandler;
use Ecotone\Api\Attribute\QueryHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
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
