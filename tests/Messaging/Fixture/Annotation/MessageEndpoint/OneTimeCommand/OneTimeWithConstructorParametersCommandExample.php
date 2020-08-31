<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Annotation\OneTimeCommand;

class OneTimeWithConstructorParametersCommandExample
{
    public function __construct(string $name)
    {

    }

    /**
     * @OneTimeCommand("doSomething")
     */
    public function execute() : void
    {

    }
}