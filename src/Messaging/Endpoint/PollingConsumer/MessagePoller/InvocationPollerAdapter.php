<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer\MessagePoller;

use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePoller;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
class InvocationPollerAdapter implements MessagePoller
{
    public function __construct(
        private object $serviceToCall,
        private string $methodName,
        private ?string $inboundRequestChannelName = null,
    ) {
    }

    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        $result = $this->serviceToCall->{$this->methodName}();
        if ($result === null) {
            return null;
        }
        $message = $result instanceof Message
            ? MessageBuilder::fromMessage($result)
            : MessageBuilder::withPayload($result);

        if ($this->inboundRequestChannelName !== null) {
            $message = $message->setHeader(MessageHeaders::INBOUND_REQUEST_CHANNEL, $this->inboundRequestChannelName);
        }

        return $message->build();
    }

    public function onConsumerStop(): void
    {
        // No cleanup needed for invocation pollers
    }
}
