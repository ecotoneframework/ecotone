<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Transformer;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Class PassThroughTransformer
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PassThroughTransformer
{
    /**
     * @param Message $message
     *
     * @return Message
     */
    public function transform(Message $message) : Message
    {
        return $message;
    }
}