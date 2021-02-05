<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Attribute\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\ReferenceCallInterceptorAnnotation;

#[Aggregate]
class AggregateCommandHandlerExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler(endpointId: "command-id")]
    public function doAction(DoStuffCommand $command) : void
    {

    }
}