<?php

namespace Ecotone\Enqueue;

use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Interop\Queue\Consumer as EnqueueConsumer;
use Interop\Queue\Message as EnqueueMessage;

class InboundMessageConverter
{
    /**
     * @var string
     */
    private $acknowledgeMode;
    /**
     * @var HeaderMapper
     */
    private $headerMapper;
    /**
     * @var string
     */
    private $acknowledgeHeaderName;
    /**
     * @var string
     */
    private $inboundEndpointId;

    public function __construct(string $inboundEndpointId, string $acknowledgeMode, string $acknowledgeHeaderName, HeaderMapper $headerMapper)
    {
        $this->acknowledgeMode = $acknowledgeMode;
        $this->headerMapper = $headerMapper;
        $this->acknowledgeHeaderName = $acknowledgeHeaderName;
        $this->inboundEndpointId = $inboundEndpointId;
    }

    public function toMessage(EnqueueMessage $source, EnqueueConsumer $consumer): MessageBuilder
    {
        $enqueueMessageHeaders = $source->getProperties();
        $messageBuilder = MessageBuilder::withPayload($source->getBody())
            ->setMultipleHeaders($this->headerMapper->mapToMessageHeaders($enqueueMessageHeaders));

        if (in_array($this->acknowledgeMode, [EnqueueAcknowledgementCallback::AUTO_ACK, EnqueueAcknowledgementCallback::MANUAL_ACK])) {
            if ($this->acknowledgeMode == EnqueueAcknowledgementCallback::AUTO_ACK) {
                $amqpAcknowledgeCallback = EnqueueAcknowledgementCallback::createWithAutoAck($consumer, $source);
            } else {
                $amqpAcknowledgeCallback = EnqueueAcknowledgementCallback::createWithManualAck($consumer, $source);
            }

            $messageBuilder = $messageBuilder
                ->setHeader($this->acknowledgeHeaderName, $amqpAcknowledgeCallback);
        }

        if (isset($enqueueMessageHeaders[MessageHeaders::MESSAGE_ID])) {
            $messageBuilder = $messageBuilder
                ->setHeader(MessageHeaders::MESSAGE_ID, $enqueueMessageHeaders[MessageHeaders::MESSAGE_ID]);
        }

        if (isset($enqueueMessageHeaders[MessageHeaders::TIMESTAMP])) {
            $messageBuilder = $messageBuilder
                ->setHeader(MessageHeaders::TIMESTAMP, $enqueueMessageHeaders[MessageHeaders::TIMESTAMP]);
        }

        $messageBuilder = $messageBuilder
            ->setHeader(MessageHeaders::CONSUMER_ENDPOINT_ID, $this->inboundEndpointId)
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, $this->acknowledgeHeaderName);

        return $messageBuilder;
    }
}
