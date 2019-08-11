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
class CommandHandlerWithClassNameInAnnotation
{
    /**
     * @return int
     * @CommandHandler(inputChannelName="input", endpointId="command-id", messageClassName=SomeCommand::class)
     */
    public function execute() : int
    {
        return 1;
    }
}