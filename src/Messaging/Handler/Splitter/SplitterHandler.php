<?php

namespace Ecotone\Messaging\Handler\Splitter;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\TypeDescriptor;
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
        private MessageChannel   $outputChannel,
        private MessageProcessor $messageProcessor,
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
            if ($payload instanceof Message) {
                $this->outputChannel->send(
                    MessageBuilder::fromMessage($payload)
                        ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $message->getHeaders()->getCorrelationId())
                        ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                        ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                        ->build()
                );
            } else {
                $this->outputChannel->send(
                    MessageBuilder::fromParentMessage($replyMessage)
                        ->setPayload($payload)
                        ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::createFromVariable($payload)->toString()))
                        ->setHeader(MessageHeaders::MESSAGE_CORRELATION_ID, $message->getHeaders()->getCorrelationId())
                        ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber + 1)
                        ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                        ->build()
                );
            }
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
