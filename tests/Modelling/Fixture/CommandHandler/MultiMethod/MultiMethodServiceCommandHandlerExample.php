<?php


namespace Test\Ecotone\Modelling\Fixture\CommandHandler\MultiMethod;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\NotUniqueHandler;

class MultiMethodServiceCommandHandlerExample
{
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