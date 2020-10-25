<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;

class ExampleEventHandlerWithServices
{
    #[EventHandler("someInput", "some-id")]
    public function doSomething($command, \stdClass $service1, \stdClass $service2) : void
    {

    }
}