<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory;

use Ecotone\Api\Attribute\AggregateEvents;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
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
