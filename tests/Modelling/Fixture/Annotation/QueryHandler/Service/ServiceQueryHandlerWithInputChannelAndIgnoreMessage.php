<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Api\Attribute\IgnorePayload;
use Ecotone\Api\Attribute\QueryHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
class ServiceQueryHandlerWithInputChannelAndIgnoreMessage
{
    #[QueryHandler('execute', 'queryHandler')]
    #[IgnorePayload]
    public function execute(stdClass $class): int
    {
    }
}
