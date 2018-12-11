<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\Assert;
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
     * @var string
     */
    private $className;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var MethodCallToMessageConverter
     */
    private $methodCallToMessageConverter;
    /**
     * @var SendAndReceiveService
     */
    private $requestReplyService;
    /**
     * @var TransactionFactory[]
     */
    private $transactionFactories;

    /**
     * GatewayProxy constructor.
     * @param string $className
     * @param string $methodName
     * @param MethodCallToMessageConverter $methodCallToMessageConverter
     * @param SendAndReceiveService $requestReplyService
     * @param array $transactionFactories
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __construct(string $className, string $methodName, MethodCallToMessageConverter $methodCallToMessageConverter, SendAndReceiveService $requestReplyService, array $transactionFactories)
    {
        Assert::allInstanceOfType($transactionFactories, TransactionFactory::class);

        $this->methodCallToMessageConverter = $methodCallToMessageConverter;
        $this->className = $className;
        $this->methodName = $methodName;
        $this->requestReplyService = $requestReplyService;
        $this->transactionFactories = $transactionFactories;
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
        $interfaceToCall = InterfaceToCall::create($this->className, $this->methodName);

        $parameters = $interfaceToCall->getParameters();
        $countArguments = count($methodArgumentValues);
        for ($index = 0; $index < $countArguments; $index++) {
            $methodArguments[] = MethodArgument::createWith($parameters[$index], $methodArgumentValues[$index]);
        }

        $replyChannel = null;
        $errorChannel = null;
        if ($interfaceToCall->hasSingleArgument() && $interfaceToCall->hasFirstParameterMessageTypeHint()) {
            /** @var Message $requestMessage */
            $requestMessage = $methodArguments[0]->value();
            $replyChannel = $requestMessage->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL) ? $requestMessage->getHeaders()->getReplyChannel() : null;
            $errorChannel = $requestMessage->getHeaders()->containsKey(MessageHeaders::ERROR_CHANNEL) ? $requestMessage->getHeaders()->getErrorChannel() : null;
            $message = $this->requestReplyService
                ->prepareForSend(MessageBuilder::fromMessage($requestMessage), $interfaceToCall)
                ->build();
        }else {
            $message = $this->methodCallToMessageConverter->convertFor($methodArguments);
            $message = $this->requestReplyService
                ->prepareForSend($message, $interfaceToCall)
                ->build();
        }

        $transactions = [];
        foreach ($this->transactionFactories as $transactionFactory) {
            $transactions[] = $transactionFactory->begin();
        }

        try {
            $this->requestReplyService->send($message);

            if ($interfaceToCall->doesItReturnFuture()) {
                $this->commitTransactions($transactions);
                return FutureReplyReceiver::create($this->requestReplyService);
            }

            $replyMessage = $this->requestReplyService->receiveReply();
            if (is_null($replyMessage) && $interfaceToCall->hasReturnValue() && !$interfaceToCall->canItReturnNull()) {
                throw InvalidArgumentException::create("{$interfaceToCall} expects value, but null was returned. If you defined errorChannel it's advised to change interface to nullable.");
            }

            $this->commitTransactions($transactions);
            if ($interfaceToCall->doesItReturnMessage() && $replyMessage) {
                $replyMessageBuilder = MessageBuilder::fromMessage($replyMessage);
                if ($replyChannel) {
                    $replyMessageBuilder->setHeader(MessageHeaders::REPLY_CHANNEL, $replyChannel);
                }
                if ($errorChannel) {
                    $replyMessageBuilder->setHeader(MessageHeaders::ERROR_CHANNEL, $errorChannel);
                }

                return $replyMessageBuilder->build();
            }

            return $replyMessage ? $replyMessage->getPayload() : null;
        }catch (\Throwable $e) {
            $this->rollbackTransactions($transactions);

            if ($e instanceof MessagingException && $e->getCause()) {
                throw $e->getCause();
            }

            throw $e;
        }
    }

    /**
     * @param Transaction[] $transactions
     */
    private function commitTransactions(array $transactions) : void
    {
        foreach ($transactions as $transaction) {
            $transaction->commit();
        }
    }

    /**
     * @param Transaction[] $transactions
     */
    private function rollbackTransactions(array $transactions) : void
    {
        foreach ($transactions as $transaction) {
            $transaction->rollback();
        }
    }
}