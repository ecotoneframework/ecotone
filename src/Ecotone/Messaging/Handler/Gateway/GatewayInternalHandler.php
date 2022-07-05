<?php

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\ErrorMessage;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class GatewayInternalHandler
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayInternalHandler
{
    private \Ecotone\Messaging\MessageChannel $requestChannel;
    private ?\Ecotone\Messaging\MessageChannel $errorChannel;
    private ?\Ecotone\Messaging\PollableChannel $replyChannel;
    private \Ecotone\Messaging\Handler\InterfaceToCall $interfaceToCall;
    private int $replyMilliSecondsTimeout;

    /**
     * GatewayInternalHandler constructor.
     * @param InterfaceToCall $interfaceToCall
     * @param MessageChannel $requestChannel
     * @param MessageChannel|null $errorChannel
     * @param PollableChannel|null $replyChannel
     * @param int $replyMilliSecondsTimeout
     */
    public function __construct(InterfaceToCall $interfaceToCall, MessageChannel $requestChannel, ?MessageChannel $errorChannel, ?PollableChannel $replyChannel, int $replyMilliSecondsTimeout)
    {
        $this->interfaceToCall = $interfaceToCall;
        $this->requestChannel = $requestChannel;
        $this->errorChannel = $errorChannel;
        $this->replyChannel = $replyChannel;
        $this->replyMilliSecondsTimeout = $replyMilliSecondsTimeout;
    }

    /**
     * @param Message $requestMessage
     * @return mixed
     * @throws MessagingException
     * @throws mixed
     */
    public function handle(Message $requestMessage)
    {
//      Gateway can be called inside service activator. So it means, we need to preserve reply channel in order to reply with it
        $previousReplyChannel = $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL) ? $requestMessage->getHeaders()->getReplyChannel() : null;

        $requestMessage = MessageBuilder::fromMessage($requestMessage);
        $replyChannel = $this->replyChannel ? $this->replyChannel : ($this->interfaceToCall->canReturnValue() ? QueueChannel::create() : null);
        if ($replyChannel) {
            $requestMessage = $requestMessage
                ->setReplyChannel($replyChannel);
        }else {
            $requestMessage = $requestMessage->removeHeader(MessageHeaders::REPLY_CHANNEL);
        }

        $requestMessage = $requestMessage->build();

        $this->requestChannel->send($requestMessage);

        $replyMessage = null;
        if ($this->interfaceToCall->canReturnValue() && $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $replyCallable = $this->getReply($requestMessage->getHeaders()->getReplyChannel());

            if ($this->interfaceToCall->doesItReturnFuture()) {
                return FutureReplyReceiver::create($replyCallable);
            }

            $replyMessage = $replyCallable();
        }

        if ($replyMessage) {
            if ($this->interfaceToCall->getReturnType()->equals(TypeDescriptor::create(Message::class))) {
                if  ($previousReplyChannel) {
                    return MessageBuilder::fromMessage($replyMessage)
                            ->setReplyChannel($previousReplyChannel)
                            ->build();
                }

                return $replyMessage;
            }
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

            $replyMessage = $this->replyMilliSecondsTimeout > 0 ? $replyChannel->receiveWithTimeout($this->replyMilliSecondsTimeout) : $replyChannel->receive();

            if (is_null($replyMessage) && !$this->interfaceToCall->canItReturnNull()) {
                throw InvalidArgumentException::create("{$this->interfaceToCall} expects value, but null was returned. Have you consider changing return value to nullable?");
            }
            if ($replyMessage instanceof ErrorMessage) {
                throw ($replyMessage->getPayload()->getCause() ? $replyMessage->getPayload()->getCause() : $replyMessage->getPayload());
            }

            return $replyMessage;
        };
    }
}