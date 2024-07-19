<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory;

use Ecotone\Modelling\Attribute\AggregateEvents;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\Identifier;
use stdClass;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
class NoIdDefinedAfterRecordingEvents
{
    #[Identifier]
    private $id;

    #[CommandHandler]
    public static function create(CreateNoIdDefinedAggregate $command): array
    {
        return [];
    }

    #[AggregateEvents]
    public function recordedEvents(): array
    {
        return [new stdClass()];
    }
}
