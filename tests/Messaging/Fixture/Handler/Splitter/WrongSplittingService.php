<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Splitter;

/**
 * Class WrongSplittingService
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class WrongSplittingService
{
    public function splittingWithReturnString() : string
    {
        return "some";
    }
}