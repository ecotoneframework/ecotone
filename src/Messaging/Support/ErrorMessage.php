<?php

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;

/**
 * Class ErrorMessage where payload is thrown exception
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class ErrorMessage implements Message
{
    private \Ecotone\Messaging\MessagingException $messagingException;
    private \Ecotone\Messaging\MessageHeaders $messageHeaders;

    /**
     * ErrorMessage constructor.
     * @param MessagingException $messagingException
     * @param MessageHeaders $messageHeaders
     */
    private function __construct(MessagingException $messagingException, MessageHeaders $messageHeaders)
    {
        $this->messagingException = $messagingException;
        $this->messageHeaders = $messageHeaders;
    }

    /**
     * @param MessagingException $messagingException
     * @return ErrorMessage
     */
    public static function create(MessagingException $messagingException) : self
    {
        return new self($messagingException, $messagingException->getFailedMessage() ? $messagingException->getFailedMessage()->getHeaders() : MessageHeaders::createEmpty());
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): MessageHeaders
    {
        return $this->messageHeaders;
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): \Ecotone\Messaging\MessagingException
    {
        return $this->messagingException;
    }
}