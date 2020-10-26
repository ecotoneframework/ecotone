<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\ReferenceCallInterceptorAnnotation;

#[Aggregate]
class AggregateCommandHandlerWithDoubledActionMethod
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler("sameChannel")]
    public function action1() : void
    {

    }

    #[CommandHandler("sameChannel")]
    public function action2() : void
    {

    }
}