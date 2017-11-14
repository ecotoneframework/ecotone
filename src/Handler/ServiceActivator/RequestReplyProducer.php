<?php

namespace Messaging\Handler\ServiceActivator;

use Messaging\Handler\ServiceActivator\MessageProcessor;
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
class RequestReplyProducer
{
    /**
     * @var MessageChannel|null
     */
    private $outputChannel;
    /**
     * @var bool
     */
    private $isReplyRequired;

    /**
     * RequestReplyProducer constructor.
     * @param MessageChannel $outputChannel
     * @param bool $isReplyRequired
     */
    public function __construct(?MessageChannel $outputChannel, bool $isReplyRequired)
    {
        $this->outputChannel = $outputChannel;
        $this->isReplyRequired = $isReplyRequired;
    }

    /**
     * @param MessagingRegistry $messagingRegistry
     * @param Message $message
     * @param MessageProcessor $messageProcessor
     * @throws MessageDeliveryException
     * @throws \Messaging\MessagingException
     */
    public function handleWithReply(Message $message, MessageProcessor $messageProcessor, MessagingRegistry $messagingRegistry) : void
    {
        $replyData = $messageProcessor->processMessage($message);

        if ($this->isReplyRequired() && $this->isReplyDataEmpty($replyData)) {
            throw MessageDeliveryException::create("Requires response but got none. {$messageProcessor}");
        }

        if ($replyData) {
            if (!$this->hasOutputChannel() && !$message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
                throw new MessageDeliveryException("Can't process {$message}, no output channel during delivery using {$messageProcessor}");
            }

            $replyChannel = $this->hasOutputChannel() ? $this->getOutputChannel() : $messagingRegistry->getMessageChannel($message->getHeaders()->getReplyChannel());
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
    private function hasOutputChannel(): bool
    {
        return (bool)$this->outputChannel;
    }

    /**
     * @inheritDoc
     */
    private function getOutputChannel(): MessageChannel
    {
        return $this->outputChannel;
    }

    /**
     * @inheritDoc
     */
    private function isReplyRequired(): bool
    {
        return $this->isReplyRequired;
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