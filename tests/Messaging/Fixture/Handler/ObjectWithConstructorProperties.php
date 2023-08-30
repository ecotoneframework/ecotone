<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Attribute;
use Ecotone\Modelling\Attribute\Identifier;

class ObjectWithConstructorProperties
{
    public function __construct(
        #[ExampleAttribute,
            Identifier]
        public string $id
    ) {
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class ExampleAttribute
{
}
