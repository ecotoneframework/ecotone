<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Annotation\OneTimeCommand;
use Ecotone\Messaging\Config\OneTimeCommandResultSet;

class OneTimeWithIncorrectResultSet
{
    /**
     * @OneTimeCommand("doSomething")
     */
    public function execute() : array
    {

    }
}