<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;

/**
 * Class ExampleEventEventHandler
 * @package Test\Ecotone\Modelling\Fixture\Annotation\EventHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class ExampleEventEventHandler
{
    /**
     * @EventHandler(inputChannelName="someInput", endpointId="some-id")
     */
    public function doSomething() : void
    {

    }
}