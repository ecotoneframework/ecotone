<?php

namespace Tests\Ecotone\Messaging\Fixture\Handler\Transformer;

use Ecotone\Messaging\Message;

/**
 * Class PassThroughTransformer
 * @package Tests\Ecotone\Messaging\Fixture\Handler\Transformer
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