<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;

/**
 * Class AggregateCommandHandlerWithReferencesExample
 * @package Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate
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