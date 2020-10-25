<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;

class ExampleEventEventHandler
{
    #[EventHandler("someInput", "some-id")]
    public function doSomething() : void
    {

    }
}