<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Attribute\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\ReferenceCallInterceptorAnnotation;

#[Aggregate]
class AggregateCommandHandlerWithRedirectionByChannelName
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler("sameChannel", "factory")]
    public static function factory() : void
    {

    }

    #[CommandHandler("sameChannel", "action")]
    public function action() : void
    {

    }
}