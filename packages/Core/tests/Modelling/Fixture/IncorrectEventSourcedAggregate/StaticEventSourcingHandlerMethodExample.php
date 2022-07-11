<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use App\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use stdClass;

#[EventSourcingAggregate]
class StaticEventSourcingHandlerMethodExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler]
    public function doSomething(): void
    {
    }

    #[EventSourcingHandler]
    public static function factory(stdClass $object)
    {
    }
}
