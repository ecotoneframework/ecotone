<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Attribute\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\Annotation\ReferenceCallInterceptorAnnotation;

#[Aggregate]
class AggregateWithNoParametersAndInputChannelAndNoIgnoreMessage
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler("command", "endpoint-command")]
    public function doCommand() : void
    {

    }

    #[QueryHandler("query", "endpoint-query")]
    public function doQuery()
    {

    }
}