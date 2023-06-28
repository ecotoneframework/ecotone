<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Attribute;
use Ecotone\Modelling\Attribute\AggregateIdentifier;

class ObjectWithConstructorProperties
{
    public function __construct(
        #[ExampleAttribute,
            AggregateIdentifier] public string $id
    ) {
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class ExampleAttribute
{
}
