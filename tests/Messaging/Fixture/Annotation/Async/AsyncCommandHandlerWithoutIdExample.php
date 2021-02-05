<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;

#[Asynchronous("asyncChannel")]
class AsyncCommandHandlerWithoutIdExample
{
    #[Asynchronous("asyncChannel")]
    #[CommandHandler]
    public function doSomething(\stdClass $event) : void
    {

    }
}