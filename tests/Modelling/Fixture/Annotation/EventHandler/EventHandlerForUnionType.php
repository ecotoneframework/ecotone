<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
class EventHandlerForUnionType
{
    #[EventHandler]
    public function doSomething(stdClass|OrderWasPlaced $event): void
    {
    }
}
