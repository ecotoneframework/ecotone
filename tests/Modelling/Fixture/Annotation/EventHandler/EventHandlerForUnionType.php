<?php


namespace Ecotone\Tests\Modelling\Fixture\Annotation\EventHandler;


use Ecotone\Modelling\Attribute\EventHandler;

class EventHandlerForUnionType
{
    #[EventHandler]
    public function doSomething(\stdClass|OrderWasPlaced $event) : void
    {

    }
}