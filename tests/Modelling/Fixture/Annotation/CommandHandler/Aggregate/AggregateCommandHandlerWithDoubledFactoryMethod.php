<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Attribute\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\ReferenceCallInterceptorAnnotation;

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