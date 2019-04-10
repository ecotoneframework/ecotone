<?php

namespace SimplyCodedSoftware\Messaging\Support;

use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class ErrorMessage where payload is thrown exception
 * @package SimplyCodedSoftware\Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class ErrorMessage implements Message
{
    /**
     * @var MessagingException
     */
    private $messagingException;
    /**
     * @var MessageHeaders
     */
    private $messageHeaders;

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
        return new self($messagingException, MessageHeaders::createEmpty());
    }

    /**
     * @param MessagingException $messagingException
     * @param MessageHeaders $messageHeaders
     * @return ErrorMessage
     */
    public static function createWithHeaders(MessagingException $messagingException, MessageHeaders $messageHeaders) : self
    {
        return new self($messagingException, $messageHeaders);
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
    public function getPayload()
    {
        return $this->messagingException;
    }
}