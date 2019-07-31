<?php

namespace Test\Ecotone\DomainModel\Fixture\Annotation\EventHandler;

use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\CommandHandler;
use Ecotone\DomainModel\Annotation\EventHandler;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * Class ExampleEventHandlerWithService
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\EventHandler
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