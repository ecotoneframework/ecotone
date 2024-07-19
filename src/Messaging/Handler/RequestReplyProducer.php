<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Processor\HandlerReplyProcessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageDeliveryException;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class RequestReplyProducer
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class RequestReplyProducer implements MessageHandler
{
    public const REQUEST_REPLY_METHOD = 1;
    public const REQUEST_SPLIT_METHOD = 2;

    public function __construct(
        private ?MessageChannel $outputChannel,
        private MessageProcessor $messageProcessor,
        private ChannelResolver $channelResolver,
        private bool $isReplyRequired,
        private bool $shouldPassThroughMessage,
        private int $method,
    ) {
    }

    public static function createRequestAndReply(string|MessageChannel|null $outputChannelName, MessageProcessor $messageProcessor, ChannelResolver $channelResolver, bool $isReplyRequired, bool $shouldPassThroughMessage = false, array $aroundInterceptors = []): MessageHandler
    {
        $outputChannel = $outputChannelName ? $channelResolver->resolve($outputChannelName) : null;

        $requestReplyHandler = new self($outputChannel, $messageProcessor, $channelResolver, $isReplyRequired, $shouldPassThroughMessage, self::REQUEST_REPLY_METHOD);
        if ($aroundInterceptors) {
            return new AroundInterceptorHandler($aroundInterceptors, new HandlerReplyProcessor($requestReplyHandler));
        } else {
            return $requestReplyHandler;
        }
    }

    public static function createRequestAndSplit(?string $outputChannelName, MessageProcessor $messageProcessor, ChannelResolver $channelResolver, array $aroundInterceptors = []): MessageHandler
    {
        $outputChannel = $outputChannelName ? $channelResolver->resolve($outputChannelName) : null;

        $requestReplyHandler = new self($outputChannel, $messageProcessor, $channelResolver, true, false, self::REQUEST_SPLIT_METHOD);
        if ($aroundInterceptors) {
            return new AroundInterceptorHandler($aroundInterceptors, new HandlerReplyProcessor($requestReplyHandler));
        } else {
            return $requestReplyHandler;
        }
    }

    public function handle(Message $requestMessage): void
    {
        $replyData = $this->messageProcessor->executeEndpoint($requestMessage);
        if ($this->shouldPassThroughMessage) {
            $replyData = $requestMessage;
        }

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
            for ($sequenceNumber = 0; $sequenceNumber < $sequenceSize; $sequenceNumber++) {
                $payload = $replyData[$sequenceNumber];
                if ($payload instanceof Message) {
                    $replyChannel->send(
                        MessageBuilder::fromMessage($payload)
                            ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $message->getHeaders()->getCorrelationId())
                            ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                            ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                            ->build()
                    );
                } else {
                    $replyChannel->send(
                        MessageBuilder::fromParentMessage($message)
                            ->setPayload($payload)
                            ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::createFromVariable($payload)->toString()))
                            ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $message->getHeaders()->getCorrelationId())
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

    public function getMessageProcessor(): MessageProcessor
    {
        return $this->messageProcessor;
    }
}
