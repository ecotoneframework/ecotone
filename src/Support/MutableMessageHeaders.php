<?php

namespace Messaging\Support;

use Messaging\MessageHeaders;

/**
 * Class MutableMessageHeaders
 * @package Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class MutableMessageHeaders extends MessageHeaders
{
    /**
     * @param string $correlationMessageId
     */
    final public function withCorrelationMessage(string $correlationMessageId) : void
    {
        $this->changeHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $correlationMessageId);
    }

    /**
     * @param string $correlationMessageId
     * @param string $causationMessageId
     */
    final public function withCausationMessage(string $correlationMessageId, string $causationMessageId) : void
    {
        $this->changeHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $correlationMessageId);
        $this->changeHeader(MessageHeaders::CAUSATION_MESSAGE_ID, $causationMessageId);
    }
}