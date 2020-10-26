<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;
use Ecotone\Messaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use Ecotone\Messaging\Annotation\Parameter\Reference;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\IgnorePayload;
use Ecotone\Modelling\Annotation\ReferenceCallInterceptorAnnotation;

#[Aggregate]
class AggregateNoInputChannelAndNoMessage
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;

    #[CommandHandler]
    #[IgnorePayload]
    public function doAction() : void
    {

    }
}