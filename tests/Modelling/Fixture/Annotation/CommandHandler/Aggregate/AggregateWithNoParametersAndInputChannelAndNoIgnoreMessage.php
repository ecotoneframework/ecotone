<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Messaging\Annotation\Parameter\Reference;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\Annotation\ReferenceCallInterceptorAnnotation;

/**
 * Class AggregateCommandHandlerExample
 * @package Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class AggregateWithNoParametersAndInputChannelAndNoIgnoreMessage
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;

    #[CommandHandler("command", "endpoint-command")]
    public function doCommand() : void
    {

    }

    #[QueryHandler("query", "endpoint-query")]
    public function doQuery()
    {

    }
}