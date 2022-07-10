<?php

namespace Test\Ecotone\Modelling\Fixture\AggregateIdFromMethod;

class CreateUser
{
    public string $id;
    public string $name;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}