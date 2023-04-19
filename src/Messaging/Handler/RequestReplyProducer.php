<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInvoker;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageDeliveryException;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ramsey\Uuid\Uuid;

/**
 * Class RequestReplyProducer
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RequestReplyProducer
{
    private const REQUEST_REPLY_METHOD = 1;
    private const REQUEST_SPLIT_METHOD = 2;

    private ?\Ecotone\Messaging\MessageChannel $outputChannel;
    private bool $isReplyRequired;
    private \Ecotone\Messaging\Handler\ChannelResolver $channelResolver;
    private MessageProcessor $messageProcessor;
    private int $method;

    private function __construct(
        ?MessageChannel $outputChannel,
        MessageProcessor $messageProcessor,
        ChannelResolver $channelResolver,
        bool $isReplyRequired,
        int $method,
    ) {
        $this->outputChannel = $outputChannel;
        $this->isReplyRequired = $isReplyRequired;
        $this->channelResolver = $channelResolver;
        $this->messageProcessor = $messageProcessor;
        $this->method = $method;
    }

    public static function createRequestAndReply(string|MessageChannel|null $outputChannelName, MessageProcessor $messageProcessor, ChannelResolver $channelResolver, bool $isReplyRequired): RequestReplyProducer
    {
        $outputChannel = $outputChannelName ? $channelResolver->resolve($outputChannelName) : null;

        return new self($outputChannel, $messageProcessor, $channelResolver, $isReplyRequired, self::REQUEST_REPLY_METHOD);
    }

    public static function createRequestAndSplit(?string $outputChannelName, MessageProcessor $messageProcessor, ChannelResolver $channelResolver): self
    {
        $outputChannel = $outputChannelName ? $channelResolver->resolve($outputChannelName) : null;

        return new self($outputChannel, $messageProcessor, $channelResolver, true, self::REQUEST_SPLIT_METHOD);
    }

    public function handleWithPossibleAroundInterceptors(Message $message): void
    {
        $methodCall = $this->messageProcessor->getMethodCall($message);
        if ($this->messageProcessor->getAroundMethodInterceptors() === []) {
            $this->executeEndpointAndSendReply($message);

            return;
        }

        $aroundMethodInvoker = new AroundMethodInvoker(
            $this->messageProcessor,
            $methodCall,
            $this->messageProcessor->getAroundMethodInterceptors(),
            $message,
            $this,
        );

        /** Execute endpoint with around and sends reply. Important as endpoint replay channel is replaced in AroundMethodInvoker */
        $result = $aroundMethodInvoker->proceed();
        if ($message->getHeaders()->hasReplyChannel() && ! is_null($result)) {
            $result = $result instanceof Message ? $result : MessageBuilder::fromMessage($message)->setPayload($result)->build();
            $message->getHeaders()->getReplyChannel()->send($result);
        }
    }

    public function executeEndpointAndSendReply(Message $requestMessage): void
    {
        $replyData = $this->messageProcessor->executeEndpoint($requestMessage);

        if ($this->isReplyRequired() && $this->isReplyDataEmpty($replyData)) {
            throw MessageDeliveryException::createWithFailedMessage("Requires response but got none. {$this->messageProcessor}", $requestMessage);
        }

        if (is_null($replyData)) {
            return;
        }

        $message = $requestMessage;
        if ($replyData instanceof Message) {
            $message = $replyData;
        }
        $replyChannel = null;
        $routingSlip = $message->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP) ? $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP) : '';
        $routingSlipChannels = explode(',', $routingSlip);

        if ($this->hasOutputChannel()) {
            $replyChannel = $this->getOutputChannel();
        } else {
            if ($routingSlip) {
                $replyChannel = $this->channelResolver->resolve(array_shift($routingSlipChannels));
            } elseif ($requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
                $replyChannel = $this->channelResolver->resolve($requestMessage->getHeaders()->getReplyChannel());
            }
        }
        $routingSlip = implode(',', $routingSlipChannels);

        if (! $replyChannel) {
            if (! $this->isReplyRequired()) {
                return;
            }

            throw MessageDeliveryException::createWithFailedMessage("Can't process {$message}, no output channel during delivery in {$this->messageProcessor}", $message);
        }

        if ($this->method === self::REQUEST_REPLY_METHOD) {
            if ($replyData instanceof Message) {
                $messageBuilder = MessageBuilder::fromMessage($replyData);
            } else {
                $messageBuilder = MessageBuilder::fromMessage($message)
                    ->setPayload($replyData);
            }

            if (! $routingSlip) {
                $messageBuilder = $messageBuilder
                    ->removeHeader(MessageHeaders::ROUTING_SLIP);
            } else {
                $messageBuilder = $messageBuilder
                    ->setHeader(MessageHeaders::ROUTING_SLIP, $routingSlip);
            }
            $replyChannel->send($messageBuilder->build());
        } else {
            if (! is_iterable($replyData)) {
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
                } else {
                    $replyChannel->send(
                        MessageBuilder::fromMessageWithNewMessageId($message)
                            ->setPayload($payload)
                            ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::createFromVariable($payload)->toString()))
                            ->setHeaderIfAbsent(MessageHeaders::MESSAGE_CORRELATION_ID, $correlationId)
                            ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                            ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                            ->build()
                    );
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
    private function getOutputChannel(): ?MessageChannel
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
