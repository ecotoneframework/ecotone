<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Annotation\OneTimeCommand;

class NoParameterOneTimeCommandExample
{
    /**
     * @OneTimeCommand("doSomething")
     */
    public function execute() : void
    {

    }
}