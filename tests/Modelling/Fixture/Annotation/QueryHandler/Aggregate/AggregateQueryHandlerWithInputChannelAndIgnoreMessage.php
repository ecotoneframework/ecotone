<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\IgnorePayload;
use Ecotone\Api\Attribute\QueryHandler;
use stdClass;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateQueryHandlerWithInputChannelAndIgnoreMessage
{
    #[QueryHandler('execute', 'queryHandler')]
    #[IgnorePayload]
    public function execute(stdClass $class): int
    {
    }
}
