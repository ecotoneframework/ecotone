<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\InvalidArgumentException;
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
    private MethodCallToMessageConverter $methodCallToMessageConverter;
    /**
     * @var MessageConverter[]
     */
    private array $messageConverters;
    private InterfaceToCall $interfaceToCall;

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
     * @param AroundInterceptorBuilder[] $aroundInterceptors
     * @param InputOutputMessageHandlerBuilder[] $sortedBeforeInterceptors
     * @param InputOutputMessageHandlerBuilder[] $sortedAfterInterceptors
     * @param object[] $endpointAnnotations
     */
    public function __construct(
        InterfaceToCall $interfaceToCall,
        MethodCallToMessageConverter $methodCallToMessageConverter,
        array $messageConverters,
        private GatewayReplyConverter $gatewayReplyConverter,
        private MessageHandler $gatewayInternalHandler
    ) {
        $this->methodCallToMessageConverter = $methodCallToMessageConverter;
        $this->messageConverters = $messageConverters;
        $this->interfaceToCall = $interfaceToCall;
    }

    /**
     * @param array $methodArgumentValues
     * @return mixed
     * @throws MessagingException
     * @throws Throwable
     */
    public function execute(array $methodArgumentValues)
    {
        $internalReplyBridge = null;
        if (count($methodArgumentValues) === 1 && ($methodArgumentValues[0] instanceof Message)) {
            $requestMessage = MessageBuilder::fromMessage($methodArgumentValues[0]);
        } else {
            $methodArguments = [];
            $parameters = $this->interfaceToCall->getInterfaceParameters();
            $countArguments = count($parameters);
            for ($index = 0; $index < $countArguments; $index++) {
                $parameter = $parameters[$index];
                if (! array_key_exists($index, $methodArgumentValues) && $parameter->hasDefaultValue()) {
                    $methodValue = $parameter->getDefaultValue();
                } else {
                    if (! array_key_exists($index, $methodArgumentValues)) {
                        throw InvalidArgumentException::create("Missing argument {$parameter->getName()} for calling {$this->interfaceToCall}");
                    }
                    $methodValue = $methodArgumentValues[$index];
                }

                $methodArguments[] = MethodArgument::createWith($parameter, $methodValue);
            }

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
        if ($this->interfaceToCall->canReturnValue()) {
            $internalReplyBridge = QueueChannel::create($this->interfaceToCall->getInterfaceName() . '::' . $this->interfaceToCall->getMethodName() . '-replyChannel');
            $requestMessage = $requestMessage
                ->setReplyChannel($internalReplyBridge);
        } else {
            $requestMessage = $requestMessage
                ->removeHeader(MessageHeaders::REPLY_CHANNEL);
        }
        $requestMessage = $requestMessage
            ->removeHeader(MessageHeaders::REPLY_CONTENT_TYPE)
            ->build();

        $this->gatewayInternalHandler->handle($requestMessage);
        $replyMessage = $internalReplyBridge ? $internalReplyBridge->receive() : null;
        if (! is_null($replyMessage) && $this->interfaceToCall->canReturnValue()) {
            if ($replyContentType !== null || ! ($this->interfaceToCall->getReturnType()->isAnything() || $this->interfaceToCall->getReturnType()->isMessage())) {
                $reply = $this->gatewayReplyConverter->convert($replyMessage, $replyContentType);
                if (! ($reply instanceof Message)) {
                    $replyMessage = MessageBuilder::fromMessage($replyMessage)
                                        ->setPayload($reply)
                                        ->build();
                } else {
                    $replyMessage = $reply;
                }
            }
        }

        if ($replyMessage) {
            if ($this->interfaceToCall->getReturnType()->isClassOfType(Message::class)) {
                if ($previousReplyChannel) {
                    return MessageBuilder::fromMessage($replyMessage)
                        ->setReplyChannel($previousReplyChannel)
                        ->build();
                }

                return $replyMessage;
            }

            return $replyMessage->getPayload();
        }
    }
}
