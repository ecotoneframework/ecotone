<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;

/**
 * Class CommandHandlerWithIncorrectConfiguration
 * @package Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class CommandHandlerWithNoCommandInformationConfiguration
{
    /**
     * @CommandHandler()
     */
    public function noAction() : void
    {

    }
}