<?php

namespace SimplyCodedSoftware\IntegrationMessaging;

/**
 * Class NullableMessageChannel
 * @package SimplyCodedSoftware\IntegrationMessaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class NullableMessageChannel implements MessageChannel
{
    const CHANNEL_NAME = 'nullableChannel';

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