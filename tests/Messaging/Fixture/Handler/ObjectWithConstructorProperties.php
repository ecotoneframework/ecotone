<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Attribute;
use Ecotone\Modelling\Attribute\Identifier;

/**
 * licence Apache-2.0
 */
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
/**
 * licence Apache-2.0
 */
class ExampleAttribute
{
}
