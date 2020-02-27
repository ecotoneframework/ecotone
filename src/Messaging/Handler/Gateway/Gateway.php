<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\MessageHeaders;
use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Throwable;

/**
 * Class GatewayProxy
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class Gateway implements NonProxyGateway
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
     * @param array $methodArgumentValues
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

            if (($this->interfaceToCall->hasSingleArgument() && ($this->interfaceToCall->hasFirstParameterMessageTypeHint() || $methodArguments[0]->value() instanceof Message))) {
                /** @var Message $requestMessage */
                $requestMessage = $methodArguments[0]->value();

                $requestMessage = MessageBuilder::fromMessage($requestMessage);
            } else {
                $requestMessage = $this->methodCallToMessageConverter->getMessageBuilderUsingPayloadConverter($methodArguments);
                $requestMessage = $this->methodCallToMessageConverter->convertFor($requestMessage, $methodArguments);

                foreach ($this->messageConverters as $messageConverter) {
                    $convertedMessageBuilder = $messageConverter->toMessage(
                        $requestMessage->getPayload(),
                        $requestMessage->getCurrentHeaders()
                    );

                    if ($convertedMessageBuilder) {
                        $requestMessage = $convertedMessageBuilder;
                    }
                }
            }


            $previousReplyChannel = $requestMessage->containsKey(MessageHeaders::REPLY_CHANNEL) ? $requestMessage->getHeaderWithName(MessageHeaders::REPLY_CHANNEL) : null;
            $replyContentType = $requestMessage->containsKey(MessageHeaders::REPLY_CONTENT_TYPE) ? MediaType::parseMediaType($requestMessage->getHeaderWithName(MessageHeaders::REPLY_CONTENT_TYPE)) : null;
            $internalReplyBridge = QueueChannel::create();
            $requestMessage = $requestMessage
                ->setReplyChannel($internalReplyBridge)
                ->removeHeader(MessageHeaders::REPLY_CONTENT_TYPE)
                ->build();

            $messageHandler = $this->buildHandler($replyContentType);
        } catch (Throwable $exception) {
            throw GatewayMessageConversionException::createFromPreviousException("Can't convert parameters to message in gateway. \n" . $exception->getMessage(), $exception);
        }

        $messageHandler->handle($requestMessage);
        $reply = $internalReplyBridge->receive();

        if ($reply) {
            if ($this->interfaceToCall->getReturnType()->isClassOfType(Message::class)) {
                if ($previousReplyChannel) {
                    return MessageBuilder::fromMessage($reply)
                        ->setReplyChannel($previousReplyChannel)
                        ->build();
                }

                return $reply;
            }

            return $reply->getPayload();
        }

        return null;
    }


    private function buildHandler(?MediaType $replyContentType) : MessageHandler
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
            ->withWrappingResultInMessage(false)
            ->withPossibilityToReplaceArgumentsInAroundInterceptors(false)
            ->withEndpointAnnotations($this->endpointAnnotations);
        $aroundInterceptorReferences = $this->aroundInterceptors;
        if (($replyContentType || !$this->interfaceToCall->getReturnType()->isAnything()) && $this->interfaceToCall->canReturnValue()) {
            $aroundInterceptorReferences[] = AroundInterceptorReference::createWithDirectObject(
                "",
                new ConversionInterceptor(
                    $this->referenceSearchService->get(ConversionService::REFERENCE_NAME),
                    $this->interfaceToCall,
                    $replyContentType
                ),
                "convert",
                ErrorChannelInterceptor::PRECEDENCE * (-1),
                ""
            );
        }
        foreach ($aroundInterceptorReferences as $aroundInterceptorReference) {
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

        return $chainHandler
            ->build(
                InMemoryChannelResolver::createWithChannelResolver($this->channelResolver, []),
                $this->referenceSearchService
            );
    }
}