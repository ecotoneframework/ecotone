<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\EventHandler;

use SimplyCodedSoftware\DomainModel\Annotation\EventHandler;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Class ExampleEventEventHandler
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\EventHandler
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