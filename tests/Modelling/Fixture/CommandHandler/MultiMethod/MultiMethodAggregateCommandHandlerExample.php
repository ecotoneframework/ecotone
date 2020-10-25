<?php


namespace Test\Ecotone\Modelling\Fixture\CommandHandler\MultiMethod;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\NotUniqueHandler;

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

    #[CommandHandler("register", "1")]
    #[NotUniqueHandler]
    public function doAction1(array $data) : void
    {

    }

    #[CommandHandler("register", "2")]
    #[NotUniqueHandler]
    public function doAction2(array $data) : void
    {

    }
}