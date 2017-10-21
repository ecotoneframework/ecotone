<?php

namespace Messaging\Handler;

use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageHandler;

/**
 * Class ServiceActivator
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class ServiceActivatingHandler implements MessageHandler
{
    /**
     * @var RequestReplyProducer
     */
    private $requestReplyProducer;
    /**
     * @var MessageProcessor
     */
    private $messageProcessor;

    /**
     * ServiceActivatingHandler constructor.
     * @param RequestReplyProducer $requestReplyProducer
     * @param MessageProcessor $messageProcessor
     */
    public function __construct(RequestReplyProducer $requestReplyProducer, MessageProcessor $messageProcessor)
    {
        $this->requestReplyProducer = $requestReplyProducer;
        $this->messageProcessor = $messageProcessor;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->requestReplyProducer->handleWithReply($message, $this->messageProcessor);
    }
}