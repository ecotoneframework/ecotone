<?php

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Future;
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
class GatewayInternalHandler
{
    public function __construct(
        private string $interfaceToCallName,
        private ?Type $returnType,
        private bool $returnTypeAllowsNull,
        private MessageChannel $requestChannel,
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
    public function handle(Message $requestMessage)
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

        $requestMessage = $requestMessage->build();

        $this->requestChannel->send($requestMessage);

        $replyMessage = null;
        if ($canReturnValue && $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $replyCallable = $this->getReply($requestMessage->getHeaders()->getReplyChannel());

            if ($this->returnType?->isClassOfType(Future::class)) {
                return FutureReplyReceiver::create($replyCallable);
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
            $replyMessage = $this->replyMilliSecondsTimeout > 0 ? $replyChannel->receiveWithTimeout($this->replyMilliSecondsTimeout) : $replyChannel->receive();

            if (is_null($replyMessage) && ! $this->returnTypeAllowsNull) {
                throw InvalidArgumentException::create("{$this->interfaceToCallName} expects value, but null was returned. Have you consider changing return value to nullable?");
            }
            if ($replyMessage instanceof ErrorMessage) {
                throw ($replyMessage->getPayload()->getCause() ?: $replyMessage->getPayload());
            }

            return $replyMessage;
        };
    }
}
