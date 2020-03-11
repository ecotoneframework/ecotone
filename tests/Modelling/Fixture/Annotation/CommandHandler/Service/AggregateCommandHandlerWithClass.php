<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

/**
 * Class CommandHandlerWithReturnValue
 * @package Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class AggregateCommandHandlerWithClass
{
    /**
     * @param \stdClass $command
     * @return int
     * @CommandHandler(endpointId="commandHandler")
     */
    public function execute(\stdClass $command) : int
    {
        return 1;
    }
}