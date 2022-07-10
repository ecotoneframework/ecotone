<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Attribute\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\ReferenceCallInterceptorAnnotation;

#[Aggregate]
class AggregateCommandHandlerWithRedirectionByClass
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler(endpointId: "factory")]
    public static function factory(DoStuffCommand $command) : void
    {

    }

    #[CommandHandler(endpointId: "action")]
    public function action(DoStuffCommand $command) : void
    {

    }
}