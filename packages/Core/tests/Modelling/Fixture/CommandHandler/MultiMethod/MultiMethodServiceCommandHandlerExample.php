<?php


namespace Test\Ecotone\Modelling\Fixture\CommandHandler\MultiMethod;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\NotUniqueHandler;

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