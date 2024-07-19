<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

/**
 * licence Apache-2.0
 */
class ExampleService
{
    public function receiveString(string $id): string
    {
        return $id;
    }
}
