<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpMessage;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageConverter\HeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\MessageConverter;
use SimplyCodedSoftware\Messaging\MessageConverter\MessageConvertingException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class EnqueueMessageConverter
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpMessageConverter implements MessageConverter
{
    /**
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var string
     */
    private $acknowledgeMode;

    /**
     * EnqueueMessageConverter constructor.
     * @param HeaderMapper $headerMapper
     * @param string $acknowledgeMode
     */
    private function __construct(HeaderMapper $headerMapper, string $acknowledgeMode)
    {
        $this->headerMapper = $headerMapper;
        $this->acknowledgeMode = $acknowledgeMode;
    }

    /**
     * @param HeaderMapper $headerMapper
     * @param string $acknowledgeMode
     * @return AmqpMessageConverter
     */
    public static function createWithMapper(HeaderMapper $headerMapper, string $acknowledgeMode): self
    {
        return new self($headerMapper, $acknowledgeMode);
    }

    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, TypeDescriptor $targetType)
    {
        if (!$targetType->isClassOfType(AmqpMessage::class)) {
            throw MessageConvertingException::create("Can't convert message");
        }

        $enqueueMessagePayload = $message->getPayload();

        $applicationHeaders = $this->headerMapper->mapFromMessageHeaders($message->getHeaders()->headers());
        $message = new \Interop\Amqp\Impl\AmqpMessage($enqueueMessagePayload, $applicationHeaders, []);

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function toMessage($source, array $messageHeaders): ?MessageBuilder
    {
        if (!($source instanceof AmqpMessage)) {
            throw MessageConvertingException::create("Can't convert message");
        }

        $messageBuilder = MessageBuilder::withPayload($source->getBody())
            ->setMultipleHeaders($this->headerMapper->mapToMessageHeaders($source->getProperties()))
            ->setMultipleHeaders($messageHeaders);

        if (in_array($this->acknowledgeMode, [AmqpAcknowledgementCallback::AUTO_ACK, AmqpAcknowledgementCallback::MANUAL_ACK])) {
            if ($this->acknowledgeMode == AmqpAcknowledgementCallback::AUTO_ACK) {
                $amqpAcknowledgeCallback = AmqpAcknowledgementCallback::createWithAutoAck($messageBuilder->getHeaderWithName(AmqpHeader::HEADER_CONSUMER), $messageBuilder->getHeaderWithName(AmqpHeader::HEADER_AMQP_MESSAGE));
            } else {
                $amqpAcknowledgeCallback = AmqpAcknowledgementCallback::createWithManualAck($messageBuilder->getHeaderWithName(AmqpHeader::HEADER_CONSUMER), $messageBuilder->getHeaderWithName(AmqpHeader::HEADER_AMQP_MESSAGE));
            }

            $messageBuilder = $messageBuilder
                ->setHeader(AmqpHeader::HEADER_ACKNOWLEDGE, $amqpAcknowledgeCallback);
        }

        if ($source->getContentType()) {
            $messageBuilder = $messageBuilder->setContentType(MediaType::parseMediaType($source->getContentType()));
        }

        return $messageBuilder;
    }
}