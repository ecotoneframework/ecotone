<?php


namespace Test\Ecotone\Messaging\Fixture\Conversion;

use Ecotone\Messaging\Message;

/**
 * Interface InCorrectInterface
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface InCorrectInterfaceExample
{
    /**
     * @return Message
     */
    public function voidWithReturnValue() : void;
}