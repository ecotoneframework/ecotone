<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Attribute\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\ReferenceCallInterceptorAnnotation;

#[Aggregate]
class AggregateCommandHandlerWithDoubledFactoryMethod
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler("sameChannel")]
    public static function factory() : void
    {

    }

    #[CommandHandler("sameChannel")]
    public static function factory2() : void
    {

    }
}