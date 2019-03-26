<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Aggregate;

use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\AggregateIdentifier;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;

/**
 * Class AggregateCommandHandlerWithReferencesExample
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class AggregateCommandHandlerWithReferencesExample
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;

    /**
     * @param DoStuffCommand $command
     * @param \stdClass      $injectedService
     * @CommandHandler(inputChannelName="input", endpointId="command-id")
     */
    public function doAction(DoStuffCommand $command, \stdClass $injectedService) : void
    {

    }
}