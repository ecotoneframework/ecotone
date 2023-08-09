<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;

#[EventSourcingAggregate]
class EventSourcingHandlerMethodWithWrongParameterCountExample
{
    #[Identifier]
    private string $id;

    #[CommandHandler]
    public function doSomething(): void
    {
    }

    #[EventSourcingHandler]
    public function factory()
    {
    }
}
