<?php

namespace Messaging\Message;

/**
 * Class NullableMessageChannel
 * @package Messaging\Message
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class NullableMessageChannel implements MessageChannel
{
    /**
     * @return NullableMessageChannel
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        return;
    }
}