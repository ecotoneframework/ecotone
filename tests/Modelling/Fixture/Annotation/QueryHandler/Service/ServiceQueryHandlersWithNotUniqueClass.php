<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Api\Attribute\QueryHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
class ServiceQueryHandlersWithNotUniqueClass
{
    #[QueryHandler]
    public function execute1(stdClass $class): int
    {
    }

    #[QueryHandler]
    public function execute2(stdClass $class): int
    {
    }
}
