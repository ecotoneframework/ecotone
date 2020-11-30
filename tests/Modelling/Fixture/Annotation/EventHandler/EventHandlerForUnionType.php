<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;


use Ecotone\Modelling\Annotation\EventHandler;

class EventHandlerForUnionType
{
    #[EventHandler]
    public function doSomething(\stdClass|OrderWasPlaced $event) : void
    {

    }
}