<?php

namespace Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\AggregateIdentifier;
use Ecotone\DomainModel\Annotation\CommandHandler;

/**
 * Class AggregateCommandHandlerWithReferencesExample
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Aggregate
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
     * @CommandHandler(inputChannelName="input", endpointId="command-id-with-references")
     */
    public function doAction(DoStuffCommand $command, \stdClass $injectedService) : void
    {

    }
}