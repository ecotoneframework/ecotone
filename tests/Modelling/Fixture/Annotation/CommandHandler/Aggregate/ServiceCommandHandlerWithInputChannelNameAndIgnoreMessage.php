<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;

/**
 * Class CommandHandlerWithReturnValue
 * @package Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class ServiceCommandHandlerWithInputChannelNameAndIgnoreMessage
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