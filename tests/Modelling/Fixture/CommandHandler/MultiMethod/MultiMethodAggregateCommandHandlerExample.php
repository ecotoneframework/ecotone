<?php


namespace Test\Ecotone\Modelling\Fixture\CommandHandler\MultiMethod;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\NotUniqueHandler;

#[Aggregate]
class MultiMethodAggregateCommandHandlerExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler("register", "1")]
    #[NotUniqueHandler]
    public function doAction1(array $data) : void
    {

    }

    #[CommandHandler("register", "2")]
    #[NotUniqueHandler]
    public function doAction2(array $data) : void
    {

    }
}