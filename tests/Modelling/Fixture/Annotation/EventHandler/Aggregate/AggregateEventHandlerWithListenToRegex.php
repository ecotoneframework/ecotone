<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateEventHandlerWithListenToRegex
{
    #[EventHandler('order.*')]
    public function execute(stdClass $class): void
    {
    }
}
