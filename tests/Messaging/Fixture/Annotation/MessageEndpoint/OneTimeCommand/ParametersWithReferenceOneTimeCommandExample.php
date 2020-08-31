<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Annotation\OneTimeCommand;

class ParametersWithReferenceOneTimeCommandExample
{
    /**
     * @OneTimeCommand("doSomething")
     */
    public function execute(string $name, string $surname, \stdClass $object) : void
    {

    }
}