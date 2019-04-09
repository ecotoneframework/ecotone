<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageDeliveryException;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\ErrorMessage;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class RequestReplyProducer
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RequestReplyProducer
{
    private const REQUEST_REPLY_METHOD = 1;
    private const REQUEST_SPLIT_METHOD = 2;

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
     * @var int
     */
    private $method;

    /**
     * RequestReplyProducer constructor.
     * @param MessageChannel|null $outputChannel
     * @param MessageProcessor $messageProcessor
     * @param ChannelResolver $channelResolver
     * @param bool $isReplyRequired
     * @param int $method
     */
    private function __construct(?MessageChannel $outputChannel, MessageProcessor $messageProcessor, ChannelResolver $channelResolver, bool $isReplyRequired, int $method)
    {
        $this->outputChannel = $outputChannel;
        $this->isReplyRequired = $isReplyRequired;
        $this->channelResolver = $channelResolver;
        $this->messageProcessor = $messageProcessor;
        $this->method = $method;
    }

    /**
     * @param string $outputChannelName
     * @param MessageProcessor $messageProcessor
     * @param ChannelResolver $channelResolver
     * @param bool $isReplyRequired
     * @return RequestReplyProducer
     * @throws DestinationResolutionException
     */
    public static function createRequestAndReply(string $outputChannelName, MessageProcessor $messageProcessor, ChannelResolver $channelResolver, bool $isReplyRequired)
    {
        $outputChannel = $outputChannelName ? $channelResolver->resolve($outputChannelName) : null;

        return new self($outputChannel, $messageProcessor, $channelResolver, $isReplyRequired, self::REQUEST_REPLY_METHOD);
    }

    /**
     * @param MessageProcessor $messageProcessor
     * @param ChannelResolver $channelResolver
     * @param bool $isReplyRequired
     * @return RequestReplyProducer
     */
    public static function createRequestAndReplyFromHeaders(MessageProcessor $messageProcessor, ChannelResolver $channelResolver, bool $isReplyRequired)
    {
        return new self(null, $messageProcessor, $channelResolver, $isReplyRequired, self::REQUEST_REPLY_METHOD);
    }

    /**
     * @param string|null $outputChannelName
     * @param MessageProcessor $messageProcessor
     * @param ChannelResolver $channelResolver
     * @return RequestReplyProducer
     * @throws DestinationResolutionException
     */
    public static function createRequestAndSplit(?string $outputChannelName, MessageProcessor $messageProcessor, ChannelResolver $channelResolver) : self
    {
        $outputChannel = $outputChannelName ? $channelResolver->resolve($outputChannelName) : null;

        return new self($outputChannel, $messageProcessor, $channelResolver, true, self::REQUEST_SPLIT_METHOD);
    }

    /**
     * @param Message $message
     * @throws DestinationResolutionException
     * @throws MessageDeliveryException
     * @throws MessageHandlingException
     * @throws MessagingException
     * @throws \Exception
     */
    public function handleWithReply(Message $message): void
    {
        try {
            $replyData = $this->messageProcessor->processMessage($message);
        }catch (\Throwable $e) {
            throw MessageHandlingException::fromOtherException($e, $message);
        }

        if ($this->isReplyRequired() && $this->isReplyDataEmpty($replyData)) {
            throw MessageDeliveryException::createWithFailedMessage("Requires response but got none. {$this->messageProcessor}", $message);
        }

        if (!is_null($replyData)) {
            $replyChannel = $this->hasOutputChannel() ? $this->getOutputChannel() : ($message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL) ? $this->channelResolver->resolve($message->getHeaders()->getReplyChannel()) : null);
            if (!$replyChannel) {
                if (!$this->isReplyRequired()) {
                    return;
                }

                throw MessageDeliveryException::createWithFailedMessage("Can't process {$message}, no output channel during delivery in {$this->messageProcessor}", $message);
            }

            if ($this->method === self::REQUEST_REPLY_METHOD) {
                if ($replyData instanceof Message) {
                    $replyChannel->send($replyData);
                    return;
                }

                $replyChannel->send(
                    MessageBuilder::fromMessage($message)
                        ->setPayload($replyData)
                        ->build()
                );
            }else {
                if (!is_iterable($replyData)) {
                    throw MessageDeliveryException::createWithFailedMessage("Can't split message {$message}, payload to split is not iterable in {$this->messageProcessor}", $message);
                }

                $sequenceSize = count($replyData);
                $correlationId = Uuid::uuid4()->toString();
                for ($sequenceNumber = 0; $sequenceNumber < $sequenceSize; $sequenceNumber++) {
                    $payload = $replyData[$sequenceNumber];
                    if ($payload instanceof Message) {
                        $replyChannel->send(
                            MessageBuilder::fromMessage($payload)
                                ->setHeaderIfAbsent(MessageHeaders::MESSAGE_CORRELATION_ID, $correlationId)
                                ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                                ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                                ->build()
                        );
                    }else {
                        $replyChannel->send(
                            MessageBuilder::fromMessage($message)
                                ->setPayload($payload)
                                ->setHeaderIfAbsent(MessageHeaders::MESSAGE_CORRELATION_ID, $correlationId)
                                ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                                ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                                ->build()
                        );
                    }
                }
            }
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

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->messageProcessor;
    }
}