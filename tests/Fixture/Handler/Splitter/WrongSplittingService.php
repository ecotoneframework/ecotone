<?php

namespace Fixture\Handler\Splitter;

/**
 * Class WrongSplittingService
 * @package Fixture\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class WrongSplittingService
{
    public function splittingWithReturnString() : string
    {
        return "some";
    }
}