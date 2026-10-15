<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use stdClass;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
class IncorrectEventTypeReturnedExample
{
    #[Identifier]
    private string $id;

    #[CommandHandler]
    public static function create(CreateIncorrectEventTypeReturnedAggregate $command): array
    {
        return [['id' => 1]];
    }

    #[Apply]
    public static function factory(stdClass $event): void
    {
    }
}
