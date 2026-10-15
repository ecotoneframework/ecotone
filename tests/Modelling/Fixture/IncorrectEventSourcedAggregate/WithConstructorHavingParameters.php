<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use stdClass;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
class WithConstructorHavingParameters
{
    #[Identifier]
    private string $id;

    public function __construct(stdClass $class)
    {
    }

    #[CommandHandler]
    public function doSomething(): void
    {
    }

    #[Apply]
    public function factory(stdClass $event)
    {
    }
}
