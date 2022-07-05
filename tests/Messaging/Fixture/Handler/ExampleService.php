<?php


namespace Tests\Ecotone\Messaging\Fixture\Handler;


class ExampleService
{
    public function receiveString(string $id) : string
    {
        return $id;
    }
}