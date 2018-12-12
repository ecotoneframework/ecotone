<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\AggregateIdentifier;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;
use SimplyCodedSoftware\DomainModel\Annotation\ReferenceCallInterceptorAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;

/**
 * Class AggregateCommandHandlerExample
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Aggregate
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
}