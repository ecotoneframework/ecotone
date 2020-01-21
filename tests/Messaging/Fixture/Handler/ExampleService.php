<?php


namespace Test\Ecotone\Messaging\Fixture\Handler;


class ExampleService
{
    public function receiveString(string $id) : string
    {
        return $id;
    }
}