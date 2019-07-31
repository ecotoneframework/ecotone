<?php

namespace Test\Ecotone\DomainModel\Fixture\Annotation\EventHandler;

use Ecotone\DomainModel\Annotation\EventHandler;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * Class ExampleEventEventHandler
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\EventHandler
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