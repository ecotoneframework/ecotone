<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Service;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;

/**
 * Class CommandHandlerWithReturnValue
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Service
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class CommandHandlerWithReturnValue
{
    /**
     * @param SomeCommand $command
     *
     * @return int
     * @CommandHandler()
     */
    public function execute(SomeCommand $command) : int
    {
        return 1;
    }
}