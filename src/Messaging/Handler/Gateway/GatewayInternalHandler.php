<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use foo\bar;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageConverter\MessageConverter;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\ErrorMessage;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use SimplyCodedSoftware\Messaging\Transaction\Transaction;
use SimplyCodedSoftware\Messaging\Transaction\TransactionFactory;

/**
 * Class GatewayInternalHandler
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayInternalHandler
{
    /**
     * @var MessageChannel|string
     */
    private $replyChannelComingFromPreviousGateway;
    /**
     * @var MessageChannel|string
     */
    private $errorChannelComingFromPreviousGateway;
    /**
     * @var array
     */
    private $transactionFactories;
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
     * @param TransactionFactory[] $transactionFactories
     * @param int $replyMilliSecondsTimeout
     * @param string|MessageChannel $replyChannelComingFromPreviousGateway
     * @param string|MessageChannel $errorChannelComingFromPreviousGateway
     */
    public function __construct(InterfaceToCall $interfaceToCall, MessageChannel $requestChannel, ?MessageChannel $errorChannel, ?PollableChannel $replyChannel, array $messageConverters, array $transactionFactories, int $replyMilliSecondsTimeout, $replyChannelComingFromPreviousGateway, $errorChannelComingFromPreviousGateway)
    {
        $this->interfaceToCall = $interfaceToCall;
        $this->replyChannelComingFromPreviousGateway = $replyChannelComingFromPreviousGateway;
        $this->errorChannelComingFromPreviousGateway = $errorChannelComingFromPreviousGateway;
        $this->transactionFactories = $transactionFactories;
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
        $transactions = [];
        foreach ($this->transactionFactories as $transactionFactory) {
            $transactions[] = $transactionFactory->begin();
        }

        try {
            try{
                $this->requestChannel->send($requestMessage);
            }catch (\Throwable $e) {
                if (!$this->errorChannel) {
                    throw MessageHandlingException::fromOtherException($e, $requestMessage);
                }

                $this->errorChannel->send(ErrorMessage::createWithFailedMessage($e, $requestMessage));
            }

            $replyMessage = null;
            if ($this->interfaceToCall->hasReturnValue()) {
                $replyCallable = $this->getReply($requestMessage, $this->replyChannel);

                if ($this->interfaceToCall->doesItReturnFuture()) {
                    $this->commitTransactions($requestMessage, $transactions);
                    return FutureReplyReceiver::create($replyCallable);
                }

                $replyMessage = $replyCallable();
            }

            if ($this->interfaceToCall->doesItReturnMessage() && $replyMessage) {
                $replyMessageBuilder = MessageBuilder::fromMessage($replyMessage);
                if ($this->replyChannelComingFromPreviousGateway) {
                    $replyMessageBuilder->setHeader(MessageHeaders::REPLY_CHANNEL, $this->replyChannelComingFromPreviousGateway);
                }
                if ($this->errorChannelComingFromPreviousGateway) {
                    $replyMessageBuilder->setHeader(MessageHeaders::ERROR_CHANNEL, $this->errorChannelComingFromPreviousGateway);
                }

                return $replyMessageBuilder->build();
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

            $this->commitTransactions($requestMessage, $transactions);
            return $reply;
        } catch (\Throwable $e) {
            $this->rollbackTransactions($requestMessage, $transactions);

            throw $e;
        }
    }


    /**
     * @param Message $requestMessage
     * @param Transaction[] $transactions
     */
    private function commitTransactions(Message $requestMessage, array $transactions): void
    {
        foreach ($transactions as $transaction) {
            $transaction->commit($requestMessage);
        }
    }

    /**
     * @param Message $requestMessage
     * @param Transaction[] $transactions
     */
    private function rollbackTransactions(Message $requestMessage, array $transactions): void
    {
        foreach ($transactions as $transaction) {
            $transaction->rollback($requestMessage);
        }
    }

    /**
     * @param Message $requestMessage
     * @param PollableChannel $replyChannel
     * @return callable
     */
    private function getReply(Message $requestMessage, PollableChannel $replyChannel) : callable
    {
        return function () use ($requestMessage, $replyChannel) {
            $replyMessage = null;
            try {
                $replyMessage = $this->replyMilliSecondsTimeout > 0 ? $replyChannel->receiveWithTimeout($this->replyMilliSecondsTimeout) : $replyChannel->receive();
            }catch (\Throwable $exception) {
                if (!$this->errorChannel) {
                    throw $exception;
                }

                $this->errorChannel->send(ErrorMessage::createWithOriginalMessage($exception, $requestMessage));
            }

            if (is_null($replyMessage) && !$this->interfaceToCall->canItReturnNull()) {
                throw InvalidArgumentException::create("{$this->interfaceToCall} expects value, but null was returned. If you defined errorChannel it's advised to change interface to nullable.");
            }
            if ($replyMessage instanceof ErrorMessage) {
                if (!$this->errorChannel) {
                    throw MessageHandlingException::fromErrorMessage($replyMessage);
                }

                $this->errorChannel->send($replyMessage->extendWithOriginalMessage($requestMessage));
                return null;
            }

            return $replyMessage;
        };
    }
}