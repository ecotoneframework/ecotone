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
class AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage
{
    /**
     * @return int
     * @CommandHandler(inputChannelName="execute", endpointId="commandHandler", ignorePayload=true)
     */
    public function execute(\stdClass $class) : int
    {
        return 1;
    }
}