<?php

namespace Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation;

/**
 * licence Apache-2.0
 */
class CreateUser
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }
}
