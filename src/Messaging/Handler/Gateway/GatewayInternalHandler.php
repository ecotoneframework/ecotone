<?php

namespace Ecotone\Messaging\Handler\Gateway;

use foo\bar;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\ErrorMessage;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Messaging\Transaction\Transaction;
use Ecotone\Messaging\Transaction\TransactionFactory;

/**
 * Class GatewayInternalHandler
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayInternalHandler
{
    /**
     * @var MessageChannel
     */
    private $requestChannel;
    /**
     * @var MessageChannel|null
     */
    private $errorChannel;
    /**
     * @var PollableChannel|null
     */
    private $replyChannel;
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var int
     */
    private $replyMilliSecondsTimeout;
    /**
     * @var array|MessageConverter[]
     */
    private $messageConverters;

    /**
     * GatewayInternalHandler constructor.
     * @param InterfaceToCall $interfaceToCall
     * @param MessageChannel $requestChannel
     * @param MessageChannel|null $errorChannel
     * @param PollableChannel|null $replyChannel
     * @param MessageConverter[] $messageConverters
     * @param int $replyMilliSecondsTimeout
     */
    public function __construct(InterfaceToCall $interfaceToCall, MessageChannel $requestChannel, ?MessageChannel $errorChannel, ?PollableChannel $replyChannel, array $messageConverters, int $replyMilliSecondsTimeout)
    {
        $this->interfaceToCall = $interfaceToCall;
        $this->requestChannel = $requestChannel;
        $this->errorChannel = $errorChannel;
        $this->replyChannel = $replyChannel;
        $this->replyMilliSecondsTimeout = $replyMilliSecondsTimeout;
        $this->messageConverters = $messageConverters;
    }

    /**
     * @param Message $requestMessage
     * @return mixed
     * @throws MessagingException
     * @throws mixed
     */
    public function handle(Message $requestMessage)
    {
        $requestMessage = MessageBuilder::fromMessage($requestMessage);
        $replyChannel = $this->replyChannel ? $this->replyChannel : QueueChannel::create();
        if ($this->interfaceToCall->hasReturnValue()) {
            $requestMessage = $requestMessage
                ->setReplyChannel($replyChannel);
            if ($this->errorChannel) {
                $requestMessage = $requestMessage
                    ->setErrorChannel($this->errorChannel ? $this->errorChannel : $this->replyChannel);
            }
        }
        $requestMessage = $requestMessage->build();


        $this->requestChannel->send($requestMessage);

        $replyMessage = null;
        if ($this->interfaceToCall->hasReturnValue()) {
            $replyCallable = $this->getReply($requestMessage, $replyChannel);

            if ($this->interfaceToCall->doesItReturnFuture()) {
                return FutureReplyReceiver::create($replyCallable);
            }

            $replyMessage = $replyCallable();
        }

        $reply = null;
        if ($replyMessage) {
            foreach ($this->messageConverters as $messageConverter) {
                $reply = $messageConverter->fromMessage(
                    $replyMessage,
                    $this->interfaceToCall->getReturnType()
                );

                if ($reply) {
                    break;
                }
            }

            if (!$reply) {
                $reply = $replyMessage ? $replyMessage->getPayload() : null;
            }
        }

        return $reply;
    }

    /**
     * @param Message $requestMessage
     * @param PollableChannel $replyChannel
     * @return callable
     */
    private function getReply(Message $requestMessage, PollableChannel $replyChannel) : callable
    {
        return function () use ($requestMessage, $replyChannel) {

            $replyMessage = $this->replyMilliSecondsTimeout > 0 ? $replyChannel->receiveWithTimeout($this->replyMilliSecondsTimeout) : $replyChannel->receive();

            if (is_null($replyMessage) && !$this->interfaceToCall->canItReturnNull()) {
                throw InvalidArgumentException::create("{$this->interfaceToCall} expects value, but null was returned. Have you consider changing return value to nullable?");
            }
            if ($replyMessage instanceof ErrorMessage) {
                throw $replyMessage->getPayload();
            }

            return $replyMessage;
        };
    }
}