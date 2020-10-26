<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\ReferenceCallInterceptorAnnotation;

#[Aggregate]
class AggregateCommandHandlerWithRedirectionByClass
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;

    #[CommandHandler(endpointId: "factory")]
    public static function factory(DoStuffCommand $command) : void
    {

    }

    #[CommandHandler(endpointId: "action")]
    public function action(DoStuffCommand $command) : void
    {

    }
}