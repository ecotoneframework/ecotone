<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer\MessagePoller;

use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagePoller;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
class InvocationPollerAdapter implements MessagePoller
{
    public function __construct(private object $serviceToCall, private string $methodName)
    {
    }

    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message
    {
        $result = $this->serviceToCall->{$this->methodName}();
        if ($result === null) {
            return null;
        }
        return $result instanceof Message
            ? $result
            : MessageBuilder::withPayload($result)->build();
    }

    public function onConsumerStop(): void
    {
        // No cleanup needed for invocation pollers
    }
}
