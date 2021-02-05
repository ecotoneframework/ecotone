<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\EventHandler;

class ExampleEventEventHandler
{
    #[EventHandler("someInput", "some-id")]
    public function doSomething() : void
    {

    }
}