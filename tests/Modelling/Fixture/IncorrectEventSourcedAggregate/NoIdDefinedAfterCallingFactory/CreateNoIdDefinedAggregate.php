<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory;

use Ecotone\Api\Attribute\EventSourcingAggregate;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
class CreateNoIdDefinedAggregate
{
    public function __construct(public int $id)
    {

    }
}
