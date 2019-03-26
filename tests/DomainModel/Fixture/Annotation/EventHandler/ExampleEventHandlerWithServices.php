<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\EventHandler;

use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;
use SimplyCodedSoftware\DomainModel\Annotation\EventHandler;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Class ExampleEventHandlerWithService
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\EventHandler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
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