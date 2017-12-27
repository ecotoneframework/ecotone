<?php

namespace Messaging\Handler\ServiceActivator;

use Messaging\Handler\MessageProcessor;
use Messaging\Handler\RequestReplyProducer;
use Messaging\Message;
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
     * ServiceActivatingHandler constructor.
     * @param RequestReplyProducer $requestReplyProducer
     */
    public function __construct(RequestReplyProducer $requestReplyProducer)
    {
        $this->requestReplyProducer = $requestReplyProducer;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->requestReplyProducer->handleWithReply($message);
    }
}