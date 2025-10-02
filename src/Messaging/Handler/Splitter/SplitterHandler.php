<?php

namespace Ecotone\Messaging\Handler\Splitter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageDeliveryException;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * @licence Apache-2.0
 */
class SplitterHandler implements MessageHandler
{
    public function __construct(
        private ?MessageChannel  $outputChannel,
        private MessageProcessor $messageProcessor,
        private ChannelResolver  $channelResolver,
        private string           $name = '',
    ) {
    }

    public function handle(Message $message): void
    {
        $replyMessage = $this->messageProcessor->process($message);

        if (! $replyMessage) {
            return;
        }

        $replyData = $replyMessage->getPayload();

        if (! is_iterable($replyData)) {
            throw MessageDeliveryException::createWithFailedMessage("Can't split message {$message}, payload to split is not iterable in {$this}", $message);
        }

        $sequenceSize = count($replyData);
        for ($sequenceNumber = 0; $sequenceNumber < $sequenceSize; $sequenceNumber++) {
            $payload = $replyData[$sequenceNumber];

            $messageToSend = $payload instanceof Message
                ? MessageBuilder::fromMessage($payload)
                    ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $message->getHeaders()->getCorrelationId())
                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                    ->build()
                : MessageBuilder::fromParentMessage($replyMessage)
                    ->setPayload($payload)
                    ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(Type::createFromVariable($payload)->toString()))
                    ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $message->getHeaders()->getCorrelationId())
                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                    ->build();

            $this->sendMessage($messageToSend);
        }
    }

    private function sendMessage(Message $message): void
    {
        if ($this->outputChannel) {
            $this->outputChannel->send($message);
            return;
        }

        $routingSlip = $message->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP)
            ? $message->getHeaders()->resolveRoutingSlip()
            : [];

        if (empty($routingSlip)) {
            throw MessageDeliveryException::createWithFailedMessage(
                'Splitter has no output channel to determine next step to send message to.',
                $message
            );
        }

        $nextStep = array_shift($routingSlip);
        $targetChannel = $this->channelResolver->resolve($nextStep);

        $messageToSend = MessageBuilder::fromMessage($message)
            ->setRoutingSlip($routingSlip)
            ->build();

        $targetChannel->send($messageToSend);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
