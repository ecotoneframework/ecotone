<?php

namespace Test\Ecotone\Modelling\Fixture\SimplifiedAggregate;

/**
 * licence Apache-2.0
 */
class IdGenerator
{
    private int $counter = 0;

    public function generate(): string
    {
        $this->counter++;

        return $this->counter;
    }
}
