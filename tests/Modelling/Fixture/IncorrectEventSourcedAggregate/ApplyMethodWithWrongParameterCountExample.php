<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
class ApplyMethodWithWrongParameterCountExample
{
    #[Identifier]
    private string $id;

    #[CommandHandler]
    public function doSomething(): void
    {
    }

    #[Apply]
    public function factory()
    {
    }
}
