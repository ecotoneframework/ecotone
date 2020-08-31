<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Annotation\OneTimeCommand;
use Ecotone\Messaging\Config\OneTimeCommandResultSet;

class OneTimeWithResultExample
{
    /**
     * @OneTimeCommand("doSomething")
     */
    public function execute() : OneTimeCommandResultSet
    {

    }
}