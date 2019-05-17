<?php


namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Interface InCorrectInterface
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Conversion
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InCorrectInterfaceExample
{
    /**
     * @return Message
     */
    public function voidWithReturnValue() : void;
}