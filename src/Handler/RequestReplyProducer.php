<?php

namespace Messaging\Handler;

use Messaging\Config\InMemoryChannelResolver;
use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageDeliveryException;
use Messaging\MessageHeaders;
use Messaging\Support\Assert;
use Messaging\Support\ErrorMessage;
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
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var MessageProcessor
     */
    private $messageProcessor;

    /**
     * RequestReplyProducer constructor.
     * @param MessageChannel|null $outputChannel
     * @param MessageProcessor $messageProcessor
     * @param ChannelResolver $channelResolver
     * @param bool $isReplyRequired
     */
    private function __construct(?MessageChannel $outputChannel, MessageProcessor $messageProcessor, ChannelResolver $channelResolver, bool $isReplyRequired)
    {
        $this->outputChannel = $outputChannel;
        $this->isReplyRequired = $isReplyRequired;
        $this->channelResolver = $channelResolver;
        $this->messageProcessor = $messageProcessor;
    }

    /**
     * @param MessageChannel|null $outputChannel
     * @param MessageProcessor $messageProcessor
     * @param ChannelResolver $channelResolver
     * @param bool $isReplyRequired
     * @return RequestReplyProducer
     */
    public static function createFrom(?MessageChannel $outputChannel, MessageProcessor $messageProcessor, ChannelResolver $channelResolver, bool $isReplyRequired)
    {
        return new self($outputChannel, $messageProcessor, $channelResolver, $isReplyRequired);
    }

    /**
     * @param Message $message
     * @throws MessageDeliveryException
     * @throws \Messaging\MessagingException
     */
    public function handleWithReply(Message $message): void
    {
        try {
            $replyData = $this->messageProcessor->processMessage($message);
        }catch (\Throwable $e) {
            $errorChannel = $this->channelResolver->resolve($message->getHeaders()->getErrorChannel());
            $errorChannel->send(ErrorMessage::createWithOriginalMessage($e, $message));

            return;
        }

        if ($this->isReplyRequired() && $this->isReplyDataEmpty($replyData)) {
            throw MessageDeliveryException::create("Requires response but got none. {$this->messageProcessor}");
        }

        if (!is_null($replyData)) {
            if (!$this->hasOutputChannel() && !$message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
                throw new MessageDeliveryException("Can't process {$message}, no output channel during delivery using {$this->messageProcessor}");
            }

            $replyChannel = $this->hasOutputChannel() ? $this->getOutputChannel() : $message->getHeaders()->getReplyChannel();
            Assert::isSubclassOf($replyChannel, MessageChannel::class, "Reply channel for service activator must be MessageChannel");

            if ($replyData instanceof Message) {
                $replyChannel->send($replyData);
                return;
            }

            $replyChannel->send(
                MessageBuilder::fromMessage($message)
                    ->setPayload($replyData)
                    ->build()
            );
        }
    }

    /**
     * @return bool
     */
    public function isReplyRequired(): bool
    {
        return $this->isReplyRequired;
    }

    /**
     * @param mixed $replyData
     * @return bool
     */
    private function isReplyDataEmpty($replyData): bool
    {
        return is_null($replyData);
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
}