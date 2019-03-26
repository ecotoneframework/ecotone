<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Service;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;

/**
 * Class CommandHandlerWithReturnValue
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Service
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class CommandHandlerWithReturnValue
{
    /**
     * @param SomeCommand $command
     *
     * @return int
     * @CommandHandler(inputChannelName="input", endpointId="command-id")
     */
    public function execute(SomeCommand $command, \stdClass $service1) : int
    {
        return 1;
    }
}