<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Attribute\AggregateIdentifierMethod;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use stdClass;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\CreateNoIdDefinedAggregate;

#[EventSourcingAggregate]
class PublicIdentifierGetMethodForEventSourcedAggregate
{
    private $internalId;

    #[CommandHandler]
    public static function create(CreateNoIdDefinedAggregate $command): array
    {
        return [new stdClass()];
    }

    #[AggregateIdentifierMethod("id")]
    public function getId()
    {
        return $this->internalId;
    }
}