<?php

namespace Messaging\Handler;

use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageDeliveryException;
use Messaging\MessageHandler;
use Messaging\MessageHeaders;
use Messaging\MessagingRegistry;
use Messaging\Support\MessageBuilder;

/**
 * Class RequestReplyProducer
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RequestReplyProducer implements MessageProducer
{
    /**
     * @var MessageChannel
     */
    private $outputChannel;
    /**
     * @var bool
     */
    private $isReplyRequired;
    /**
     * @var MessagingRegistry
     */
    private $messagingRegistry;

    /**
     * RequestReplyProducer constructor.
     * @param MessagingRegistry $messagingRegistry
     */
    public function __construct(MessagingRegistry $messagingRegistry)
    {
        $this->isReplyRequired = false;
        $this->messagingRegistry = $messagingRegistry;
    }

    /**
     * @param Message $message
     * @param MessageProcessor $messageProcessor
     * @throws MessageDeliveryException
     * @throws \Messaging\MessagingException
     */
    public function handleWithReply(Message $message, MessageProcessor $messageProcessor) : void
    {
        $replyData = $messageProcessor->processMessage($message);

        if ($this->isReplyRequired() && $this->isReplyDataEmpty($replyData)) {
            throw MessageDeliveryException::create("Requires response but got none. {$messageProcessor}");
        }

        if ($replyData) {
            if (!$this->hasOutputChannel() && !$message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
                throw new MessageDeliveryException("Can't process {$message}, no output channel during delivery using {$messageProcessor}");
            }

            $replyChannel = $this->hasOutputChannel() ? $this->getOutputChannel() : $this->messagingRegistry->getMessageChannel($message->getHeaders()->getReplyChannel());
            $replyChannel->send(
                MessageBuilder::fromMessage($message)
                    ->setPayload($replyData)
                    ->build()
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function setOutputChannel(MessageChannel $outputChannel): void
    {
        $this->outputChannel = $outputChannel;
    }

    /**
     * @inheritDoc
     */
    public function hasOutputChannel(): bool
    {
        return (bool)$this->outputChannel;
    }

    /**
     * @inheritDoc
     */
    public function getOutputChannel(): MessageChannel
    {
        return $this->outputChannel;
    }

    /**
     * @inheritDoc
     */
    public function isReplyRequired(): bool
    {
        return $this->isReplyRequired;
    }

    /**
     * @inheritDoc
     */
    public function requireReply(): void
    {
        $this->isReplyRequired = true;
    }

    /**
     * @param mixed $replyData
     * @return bool
     */
    private function isReplyDataEmpty($replyData) : bool
    {
        return !(bool)$replyData;
    }
}