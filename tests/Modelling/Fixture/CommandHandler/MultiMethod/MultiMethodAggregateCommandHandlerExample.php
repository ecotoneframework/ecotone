<?php


namespace Test\Ecotone\Modelling\Fixture\CommandHandler\MultiMethod;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;

/**
 * Class MultiMethod
 * @package Test\Ecotone\Modelling\Fixture\CommandHandler\MultiMethod
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class MultiMethodAggregateCommandHandlerExample
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;

    /**
     * @param array $data
     * @CommandHandler(endpointId="1", inputChannelName="register", mustBeUnique=false)
     */
    public function doAction1(array $data) : void
    {

    }

    /**
     * @param array $data
     * @CommandHandler(endpointId="2", inputChannelName="register", mustBeUnique=false)
     */
    public function doAction2(array $data) : void
    {

    }
}