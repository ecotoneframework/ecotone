<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ramsey\Uuid\Uuid;
use Throwable;

/**
 * Class GatewayProxy
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class Gateway implements NonProxyGateway
{
    /**
     * @param MessageConverter[] $messageConverters
     */
    public function __construct(
        private MethodCallToMessageConverter $methodCallToMessageConverter,
        private ?Type $returnType,
        private array $messageConverters,
        private GatewayReplyConverter $gatewayReplyConverter,
        private MessageHandler $gatewayInternalHandler
    ) {
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
            $requestMessage = $this->methodCallToMessageConverter->convert($methodArgumentValues);

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

        $canReturnValue = $this->returnType?->isVoid() === false;

        $previousReplyChannel = $requestMessage->containsKey(MessageHeaders::REPLY_CHANNEL) ? $requestMessage->getHeaderWithName(MessageHeaders::REPLY_CHANNEL) : null;
        $replyContentType = $requestMessage->containsKey(MessageHeaders::REPLY_CONTENT_TYPE) ? MediaType::parseMediaType($requestMessage->getHeaderWithName(MessageHeaders::REPLY_CONTENT_TYPE)) : null;
        if ($canReturnValue) {
            $internalReplyBridge = QueueChannel::create(Uuid::uuid4() . '-replyChannel');
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
        if (! is_null($replyMessage) && $canReturnValue) {
            if ($replyContentType !== null || ! ($this->returnType?->isAnything() || $this->returnType?->isMessage())) {
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
            if ($this->returnType?->isClassOfType(Message::class)) {
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
