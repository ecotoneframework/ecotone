<?php

namespace Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation;

class CreateUser
{
    public function __construct(public readonly string $name)
    {

    }
}