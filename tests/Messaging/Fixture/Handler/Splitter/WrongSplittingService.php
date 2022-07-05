<?php

namespace Ecotone\Tests\Messaging\Fixture\Handler\Splitter;

/**
 * Class WrongSplittingService
 * @package Ecotone\Tests\Messaging\Fixture\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class WrongSplittingService
{
    public function splittingWithReturnString() : string
    {
        return "some";
    }
}