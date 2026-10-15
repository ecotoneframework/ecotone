<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Api\Attribute\QueryHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
class ServiceQueryHandlerWithClass
{
    #[QueryHandler(endpointId: 'queryHandler')]
    public function execute(stdClass $class): int
    {
    }
}
