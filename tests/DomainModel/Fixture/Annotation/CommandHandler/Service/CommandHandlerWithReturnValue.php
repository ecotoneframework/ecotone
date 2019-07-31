<?php

namespace Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\CommandHandler;

/**
 * Class CommandHandlerWithReturnValue
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Service
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