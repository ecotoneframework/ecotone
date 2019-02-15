<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageConverter\DefaultHeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\HeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\MessageConverter;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\ErrorMessage;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use SimplyCodedSoftware\Messaging\Transaction\Transaction;
use SimplyCodedSoftware\Messaging\Transaction\TransactionFactory;

/**
 * Class GatewayProxy
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class Gateway
{
    /**
     * @var MethodCallToMessageConverter
     */
    private $methodCallToMessageConverter;
    /**
     * @var MessageConverter[]
     */
    private $messageConverters;
    /**
     * @var TransactionFactory[]
     */
    private $transactionFactories;
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var PollableChannel|null
     */
    private $replyChannel;
    /**
     * @var MessageChannel|null
     */
    private $errorChannel;
    /**
     * @var int
     */
    private $replyMilliSecondsTimeout;
    /**
     * @var MessageChannel
     */
    private $requestChannel;

    /**
     * GatewayProxy constructor.
     * @param InterfaceToCall $interfaceToCall
     * @param MethodCallToMessageConverter $methodCallToMessageConverter
     * @param MessageConverter[] $messageConverters
     * @param array $transactionFactories
     * @param MessageChannel $requestChannel
     * @param PollableChannel|null $replyChannel
     * @param MessageChannel|null $errorChannel
     * @param int $replyMilliSecondsTimeout
     * @throws MessagingException
     */
    public function __construct(InterfaceToCall $interfaceToCall, MethodCallToMessageConverter $methodCallToMessageConverter, array $messageConverters, array $transactionFactories, MessageChannel $requestChannel, ?PollableChannel $replyChannel, ?MessageChannel $errorChannel, int $replyMilliSecondsTimeout)
    {
        Assert::allInstanceOfType($transactionFactories, TransactionFactory::class);

        $this->methodCallToMessageConverter = $methodCallToMessageConverter;
        $this->transactionFactories = $transactionFactories;
        $this->messageConverters = $messageConverters;
        $this->interfaceToCall = $interfaceToCall;
        $this->replyChannel = $replyChannel;
        $this->errorChannel = $errorChannel;
        $this->replyMilliSecondsTimeout = $replyMilliSecondsTimeout;
        $this->requestChannel = $requestChannel;
    }

    /**
     * @param array|MethodArgument[] $methodArgumentValues
     * @return mixed
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \Throwable
     */
    public function execute(array $methodArgumentValues)
    {
        $methodArguments = [];

        $parameters = $this->interfaceToCall->getParameters();
        $countArguments = count($methodArgumentValues);
        for ($index = 0; $index < $countArguments; $index++) {
            $methodArguments[] = MethodArgument::createWith($parameters[$index], $methodArgumentValues[$index]);
        }

        $replyChannelComingFromPreviousGateway = null;
        $errorChannelComingFromPreviousGateway = null;
        if ($this->interfaceToCall->hasSingleArgument() && $this->interfaceToCall->hasFirstParameterMessageTypeHint()) {
            /** @var Message $requestMessage */
            $requestMessage = $methodArguments[0]->value();
            $replyChannelComingFromPreviousGateway = $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL) ? $requestMessage->getHeaders()->getReplyChannel() : null;
            $errorChannelComingFromPreviousGateway = $requestMessage->getHeaders()->containsKey(MessageHeaders::ERROR_CHANNEL) ? $requestMessage->getHeaders()->getErrorChannel() : null;

            $requestMessage = MessageBuilder::fromMessage($requestMessage);
        } else {
            $payloadValue = $this->methodCallToMessageConverter->getPayloadArgument($methodArguments);
            $requestMessage = MessageBuilder::withPayload($payloadValue);
            $requestMessage = $this->methodCallToMessageConverter->convertFor($requestMessage, $methodArguments);

            foreach ($this->messageConverters as $messageConverter) {
                $convertedMessageBuilder = $messageConverter->toMessage(
                    $payloadValue,
                    $requestMessage->getCurrentHeaders()
                );

                if ($convertedMessageBuilder) {
                    $requestMessage = $convertedMessageBuilder;
                }
            }
        }

        $replyChannel = $this->replyChannel;
        if ($this->interfaceToCall->hasReturnValue()) {
            if (!$replyChannel) {
                $replyChannel = QueueChannel::create();
                $requestMessage = $requestMessage
                    ->setReplyChannel($replyChannel);
            }
            $requestMessage = $requestMessage
                ->setErrorChannel($this->errorChannel ? $this->errorChannel : $replyChannel);
        }

        $requestMessage = $requestMessage->build();
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

                $this->errorChannel->send(ErrorMessage::createWithOriginalMessage($e, $requestMessage));
            }

            $replyMessage = null;
            if ($this->interfaceToCall->hasReturnValue()) {
                $replyCallable = $this->getReply($replyChannel);

                if ($this->interfaceToCall->doesItReturnFuture()) {
                    $this->commitTransactions($requestMessage, $transactions);
                    return FutureReplyReceiver::create($replyCallable);
                }

                $replyMessage = $replyCallable();
            }

            if ($this->interfaceToCall->doesItReturnMessage() && $replyMessage) {
                $replyMessageBuilder = MessageBuilder::fromMessage($replyMessage);
                if ($replyChannelComingFromPreviousGateway) {
                    $replyMessageBuilder->setHeader(MessageHeaders::REPLY_CHANNEL, $replyChannelComingFromPreviousGateway);
                }
                if ($errorChannelComingFromPreviousGateway) {
                    $replyMessageBuilder->setHeader(MessageHeaders::ERROR_CHANNEL, $errorChannelComingFromPreviousGateway);
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

            if ($e instanceof MessagingException && $e->getCause()) {
                throw $e->getCause();
            }

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
     * @param PollableChannel $replyChannel
     * @return callable
     */
    private function getReply(PollableChannel $replyChannel) : callable
    {
        return function () use ($replyChannel) {
            $replyMessage = $this->replyMilliSecondsTimeout > 0 ? $replyChannel->receiveWithTimeout($this->replyMilliSecondsTimeout) : $replyChannel->receive();

            if (is_null($replyMessage) && !$this->interfaceToCall->canItReturnNull()) {
                throw InvalidArgumentException::create("{$this->interfaceToCall} expects value, but null was returned. If you defined errorChannel it's advised to change interface to nullable.");
            }
            if ($replyMessage instanceof ErrorMessage) {
                throw MessageHandlingException::fromErrorMessage($replyMessage);
            }

            return $replyMessage;
        };
    }
}