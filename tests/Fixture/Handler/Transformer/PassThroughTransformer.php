<?php

namespace Fixture\Handler\Transformer;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class PassThroughTransformer
 * @package Fixture\Handler\Transformer
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