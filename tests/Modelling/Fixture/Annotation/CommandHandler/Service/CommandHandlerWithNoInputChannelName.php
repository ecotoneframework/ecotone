<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

/**
 * Class CommandHandlerWithIncorrectConfiguration
 * @package Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class CommandHandlerWithNoInputChannelName
{
    /**
     * @CommandHandler()
     */
    public function noAction() : void
    {

    }
}