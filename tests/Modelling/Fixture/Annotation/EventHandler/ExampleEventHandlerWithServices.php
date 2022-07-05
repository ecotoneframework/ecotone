<?php

namespace Ecotone\Tests\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\EventHandler;

class ExampleEventHandlerWithServices
{
    #[EventHandler("someInput", "some-id")]
    public function doSomething($command, \stdClass $service1, \stdClass $service2) : void
    {

    }
}