<?php

namespace Ecotone\Tests\Messaging\Fixture\Handler\Transformer;

use Ecotone\Messaging\Message;

/**
 * Class PassThroughTransformer
 * @package Ecotone\Tests\Messaging\Fixture\Handler\Transformer
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