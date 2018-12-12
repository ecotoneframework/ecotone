<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Service;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;

/**
 * Class CommandHandlerWithIncorrectConfiguration
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Service
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