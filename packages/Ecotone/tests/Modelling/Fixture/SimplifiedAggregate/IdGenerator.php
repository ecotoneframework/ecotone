<?php

namespace Test\Ecotone\Modelling\Fixture\SimplifiedAggregate;

class IdGenerator
{
    private int $counter = 0;

    public function generate(): string
    {
        $this->counter++;

        return $this->counter;
    }
}
