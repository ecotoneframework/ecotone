<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

class EventHandlerForUnionType
{
    #[EventHandler]
    public function doSomething(stdClass|OrderWasPlaced $event): void
    {
    }
}
