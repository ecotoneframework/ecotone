<?php

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Future;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptable;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\ErrorMessage;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ramsey\Uuid\Uuid;

/**
 * Class GatewayInternalHandler
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class GatewayInternalProcessor implements MessageProcessor, AroundInterceptable
{
    /**
     * @param string[] $routingSlipChannels
     */
    public function __construct(
        private string $interfaceToCallName,
        private ?Type $returnType,
        private bool $returnTypeAllowsNull,
        private MessageChannel $requestChannel,
        private array $routingSlipChannels,
        private ?PollableChannel $replyChannel,
        private int $replyMilliSecondsTimeout
    ) {
    }

    /**
     * @param Message $requestMessage
     * @return mixed
     * @throws MessagingException
     * @throws mixed
     */
    public function process(Message $requestMessage): ?Message
    {
        //      Gateway can be called inside service activator. So it means, we need to preserve reply channel in order to reply with it
        $previousReplyChannel = $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL) ? $requestMessage->getHeaders()->getReplyChannel() : null;

        $canReturnValue = $this->returnType?->isVoid() === false;

        $requestMessage = MessageBuilder::fromMessage($requestMessage);
        $replyChannel = $this->replyChannel ?: ($canReturnValue ? QueueChannel::create(Uuid::uuid4() . '-replyChannel') : null);
        if ($replyChannel) {
            $requestMessage = $requestMessage
                ->setReplyChannel($replyChannel);
        } else {
            $requestMessage = $requestMessage->removeHeader(MessageHeaders::REPLY_CHANNEL);
        }

        $requestMessage = $requestMessage
            ->setRoutingSlip(array_merge($this->routingSlipChannels, $requestMessage->getRoutingSlip()))
            ->build();

        $this->requestChannel->send($requestMessage);

        $replyMessage = null;
        if ($canReturnValue && $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $replyCallable = $this->getReply($requestMessage->getHeaders()->getReplyChannel());

            if ($this->returnType?->isClassOfType(Future::class)) {
                return MessageBuilder::fromMessage($requestMessage)
                    ->setPayload(FutureReplyReceiver::create($replyCallable))
                    ->build();
            }

            $replyMessage = $replyCallable();
        }

        if ($replyMessage instanceof Message && $previousReplyChannel) {
            $replyMessage = MessageBuilder::fromMessage($replyMessage)
                ->setReplyChannel($previousReplyChannel)
                ->build();
        }

        return $replyMessage;
    }

    /**
     * @param PollableChannel $replyChannel
     * @return callable
     */
    private function getReply(PollableChannel $replyChannel): callable
    {
        return function () use ($replyChannel) {
            $replyMessage = $this->replyMilliSecondsTimeout > 0 ? $replyChannel->receiveWithTimeout(PollingMetadata::create('gateway_reply')->setFixedRateInMilliseconds($this->replyMilliSecondsTimeout)) : $replyChannel->receive();

            if (is_null($replyMessage) && ! $this->returnTypeAllowsNull) {
                throw InvalidArgumentException::create("{$this->interfaceToCallName} expects value, but null was returned. Have you consider changing return value to nullable?");
            }
            if ($replyMessage instanceof ErrorMessage) {
                throw MessageHandlingException::create(
                    $replyMessage->getExceptionMessage(),
                    $replyMessage->getExceptionCode(),
                );
            }

            return $replyMessage;
        };
    }

    public function getObjectToInvokeOn(Message $message): string|object
    {
        return $this;
    }

    public function getMethodName(): string
    {
        return 'process';
    }

    public function getArguments(Message $message): array
    {
        return [$message];
    }
}
