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
class CommandHandlerWithClassAndEndpointId
{
    /**
     * @param \stdClass $command
     * @return int
     * @CommandHandler(endpointId="endpointId")
     */
    public function execute(\stdClass $command) : int
    {
        return 1;
    }
}