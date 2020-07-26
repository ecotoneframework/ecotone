<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;

class ExampleEventHandlerWithServices
{
    /**
     * @EventHandler(inputChannelName="someInput", endpointId="some-id")
     * @param           $command
     * @param \stdClass $service1
     * @param \stdClass $service2
     */
    public function doSomething($command, \stdClass $service1, \stdClass $service2) : void
    {

    }
}