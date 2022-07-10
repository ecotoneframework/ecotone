<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Attribute\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\Attribute\ReferenceCallInterceptorAnnotation;

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