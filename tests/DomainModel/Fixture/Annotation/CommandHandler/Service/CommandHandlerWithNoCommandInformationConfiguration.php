<?php

namespace Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\CommandHandler;

/**
 * Class CommandHandlerWithIncorrectConfiguration
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Service
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