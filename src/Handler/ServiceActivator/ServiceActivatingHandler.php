<?php

namespace Messaging\Handler\ServiceActivator;

use Messaging\Handler\ServiceActivator\MessageProcessor;
use Messaging\Handler\ServiceActivator\RequestReplyProducer;
use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageHandler;
use Messaging\MessagingRegistry;

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
     * @var MessagingRegistry
     */
    private $messagingRegistry;

    /**
     * ServiceActivatingHandler constructor.
     * @param RequestReplyProducer $requestReplyProducer
     * @param MessageProcessor $messageProcessor
     * @param MessagingRegistry $messagingRegistry
     */
    public function __construct(RequestReplyProducer $requestReplyProducer, MessageProcessor $messageProcessor, MessagingRegistry $messagingRegistry)
    {
        $this->requestReplyProducer = $requestReplyProducer;
        $this->messageProcessor = $messageProcessor;
        $this->messagingRegistry = $messagingRegistry;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->requestReplyProducer->handleWithReply($message, $this->messageProcessor, $this->messagingRegistry);
    }
}