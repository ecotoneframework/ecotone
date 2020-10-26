<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\ReferenceCallInterceptorAnnotation;

#[Aggregate]
class AggregateCommandHandlerWithFactoryMethod
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;

    #[CommandHandler(endpointId: "factory-id")]
    public static function doAction(DoStuffCommand $command) : void
    {

    }
}