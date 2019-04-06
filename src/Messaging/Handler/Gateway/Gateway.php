<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
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
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var iterable|AroundInterceptorReference[]
     */
    private $aroundInterceptors;
    /**
     * @var iterable|object[]
     */
    private $endpointAnnotations;

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
     * @param ReferenceSearchService $referenceSearchService
     * @param AroundInterceptorReference[] $aroundInterceptors
     * @param object[] $endpointAnnotations
     * @throws MessagingException
     */
    public function __construct(
        InterfaceToCall $interfaceToCall, MethodCallToMessageConverter $methodCallToMessageConverter,
        array $messageConverters, array $transactionFactories, MessageChannel $requestChannel,
        ?PollableChannel $replyChannel, ?MessageChannel $errorChannel, int $replyMilliSecondsTimeout,
        ReferenceSearchService $referenceSearchService, iterable $aroundInterceptors,
        iterable $endpointAnnotations
    )
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
        $this->referenceSearchService = $referenceSearchService;
        $this->aroundInterceptors = $aroundInterceptors;
        $this->endpointAnnotations = $endpointAnnotations;
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

        $parameters = $this->interfaceToCall->getInterfaceParameters();
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

        $internalReplyBridgeName = Uuid::uuid4()->toString();
        $internalReplyBridge = QueueChannel::create();
        $gatewayInternalHandler = new GatewayInternalHandler(
            $this->interfaceToCall,
            $this->requestChannel,
            $this->errorChannel,
            $replyChannel,
            $this->messageConverters,
            $this->transactionFactories,
            $this->replyMilliSecondsTimeout,
            $replyChannelComingFromPreviousGateway,
            $errorChannelComingFromPreviousGateway
        );
        $serviceActivator = ServiceActivatorBuilder::createWithDirectReference($gatewayInternalHandler, "handle")
            ->withEndpointAnnotations($this->endpointAnnotations)
            ->withOutputMessageChannel($internalReplyBridgeName);
        foreach ($this->aroundInterceptors as $aroundInterceptorReference) {
            $serviceActivator->addAroundInterceptor($aroundInterceptorReference);
        }
        $serviceActivator = $serviceActivator
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([$internalReplyBridgeName => $internalReplyBridge]),
                                $this->referenceSearchService
                            );

        try {
            $serviceActivator->handle($requestMessage);
        }catch (\Throwable $exception) {
            if (!$this->errorChannel) {
                if ($exception instanceof MessagingException && $exception->getCause()) {
                    throw $exception->getCause();
                }

                throw $exception;
            }

            if (!($exception instanceof MessagingException)) {
                $exception = MessageHandlingException::fromOtherException($exception, $requestMessage);
            }

            $this->errorChannel->send(ErrorMessage::create($exception));
        }

        $reply = $internalReplyBridge->receive();

        if ($this->interfaceToCall->getReturnType()->isClassOfType(Message::class)) {
            return $reply;
        }
        if ($reply) {
            return $reply->getPayload();
        }

        return null;
    }
}