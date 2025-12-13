<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

/**
 * licence Apache-2.0
 */
class OrderWasRemovedWithIdentifier
{
    public function __construct(
        public string $id
    ) {
    }
}
