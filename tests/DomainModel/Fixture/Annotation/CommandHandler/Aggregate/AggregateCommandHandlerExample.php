<?php

namespace Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\AggregateIdentifier;
use Ecotone\DomainModel\Annotation\CommandHandler;
use Ecotone\DomainModel\Annotation\ReferenceCallInterceptorAnnotation;
use Ecotone\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;

/**
 * Class AggregateCommandHandlerExample
 * @package Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class AggregateCommandHandlerExample
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;

    /**
     * @param DoStuffCommand $command
     * @CommandHandler(endpointId="command-id")
     */
    public function doAction(DoStuffCommand $command) : void
    {

    }

    public function doAnotherAction(DoStuffCommand $command) : void
    {

    }
}