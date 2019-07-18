<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageConverter\MessageConverter;
use SimplyCodedSoftware\Messaging\MessageHandler;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Throwable;

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
    private $gatewayRequestChannel;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var iterable|AroundInterceptorReference[]
     */
    private $aroundInterceptors;
    /**
     * @var InputOutputMessageHandlerBuilder[]
     */
    private $sortedBeforeInterceptors = [];
    /**
     * @var InputOutputMessageHandlerBuilder[]
     */
    private $sortedAfterInterceptors = [];
    /**
     * @var iterable|object[]
     */
    private $endpointAnnotations;
    /**
     * @var ChannelResolver
     */
    private $channelResolver;

    /**
     * GatewayProxy constructor.
     * @param InterfaceToCall $interfaceToCall
     * @param MethodCallToMessageConverter $methodCallToMessageConverter
     * @param MessageConverter[] $messageConverters
     * @param MessageChannel $requestChannel
     * @param PollableChannel|null $replyChannel
     * @param MessageChannel|null $errorChannel
     * @param int $replyMilliSecondsTimeout
     * @param ReferenceSearchService $referenceSearchService
     * @param ChannelResolver $channelResolver
     * @param AroundInterceptorReference[] $aroundInterceptors
     * @param InputOutputMessageHandlerBuilder[] $sortedBeforeInterceptors
     * @param InputOutputMessageHandlerBuilder[] $sortedAfterInterceptors
     * @param object[] $endpointAnnotations
     */
    public function __construct(
        InterfaceToCall $interfaceToCall, MethodCallToMessageConverter $methodCallToMessageConverter,
        array $messageConverters, MessageChannel $requestChannel,
        ?PollableChannel $replyChannel, ?MessageChannel $errorChannel, int $replyMilliSecondsTimeout,
        ReferenceSearchService $referenceSearchService, ChannelResolver $channelResolver,
        iterable $aroundInterceptors, iterable $sortedBeforeInterceptors, iterable $sortedAfterInterceptors, iterable $endpointAnnotations
    )
    {
        $this->methodCallToMessageConverter = $methodCallToMessageConverter;
        $this->messageConverters = $messageConverters;
        $this->interfaceToCall = $interfaceToCall;
        $this->replyChannel = $replyChannel;
        $this->errorChannel = $errorChannel;
        $this->replyMilliSecondsTimeout = $replyMilliSecondsTimeout;
        $this->gatewayRequestChannel = $requestChannel;
        $this->referenceSearchService = $referenceSearchService;
        $this->aroundInterceptors = $aroundInterceptors;
        $this->endpointAnnotations = $endpointAnnotations;
        $this->sortedBeforeInterceptors = $sortedBeforeInterceptors;
        $this->sortedAfterInterceptors = $sortedAfterInterceptors;
        $this->channelResolver = $channelResolver;
    }

    /**
     * @param array|MethodArgument[] $methodArgumentValues
     * @return mixed
     * @throws MessagingException
     * @throws Throwable
     */
    public function execute(array $methodArgumentValues)
    {
        $methodArguments = [];

        try {
            $parameters = $this->interfaceToCall->getInterfaceParameters();
            $countArguments = count($parameters);
            for ($index = 0; $index < $countArguments; $index++) {
                $methodArguments[] = MethodArgument::createWith($parameters[$index], $methodArgumentValues[$index]);
            }

            $replyChannelComingFromPreviousGateway = null;
            $errorChannelComingFromPreviousGateway = null;
            if (($this->interfaceToCall->hasSingleArgument() && ($this->interfaceToCall->hasFirstParameterMessageTypeHint() || $methodArguments[0]->value() instanceof Message))) {
                /** @var Message $requestMessage */
                $requestMessage = $methodArguments[0]->value();

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

            $internalReplyBridge = QueueChannel::create();
            $requestMessage = $requestMessage
                ->setReplyChannel($internalReplyBridge)
                ->build();

            $messageHandler = $this->buildHandler($internalReplyBridge);
        } catch (Throwable $exception) {
            throw GatewayMessageConversionException::createFromPreviousException("Can't convert parameters to message in gateway. \n" . $exception->getMessage(), $exception);
        }

        $messageHandler->handle($requestMessage);
        $reply = $internalReplyBridge->receive();

        if ($this->interfaceToCall->getReturnType()->isClassOfType(Message::class)) {
            return $reply;
        }
        if ($reply) {
            return $reply->getPayload();
        }

        return null;
    }

    /**
     * @param QueueChannel $internalReplyBridge
     * @return ServiceActivatorBuilder|MessageHandler
     * @throws MessagingException
     */
    private function buildHandler(QueueChannel $internalReplyBridge)
    {
        $gatewayInternalHandler = new GatewayInternalHandler(
            $this->interfaceToCall,
            $this->gatewayRequestChannel,
            $this->errorChannel,
            $this->replyChannel,
            $this->messageConverters,
            $this->replyMilliSecondsTimeout
        );

        $gatewayInternalHandler = ServiceActivatorBuilder::createWithDirectReference($gatewayInternalHandler, "handle")
            ->withEndpointAnnotations($this->endpointAnnotations);
        foreach ($this->aroundInterceptors as $aroundInterceptorReference) {
            $gatewayInternalHandler->addAroundInterceptor($aroundInterceptorReference);
        }


        $chainHandler = ChainMessageHandlerBuilder::create();
        foreach ($this->sortedBeforeInterceptors as $beforeInterceptor) {
            $chainHandler = $chainHandler->chain($beforeInterceptor);
        }
        $chainHandler = $chainHandler->chain($gatewayInternalHandler);
        foreach ($this->sortedAfterInterceptors as $afterInterceptor) {
            $chainHandler = $chainHandler->chain($afterInterceptor);
        }

        $internalReplyBridgeName = Uuid::uuid4()->toString();
        return $chainHandler
            ->withOutputMessageChannel($internalReplyBridgeName)
            ->build(
                InMemoryChannelResolver::createWithChannelResolver($this->channelResolver, [$internalReplyBridgeName => $internalReplyBridge]),
                $this->referenceSearchService
            );
    }
}