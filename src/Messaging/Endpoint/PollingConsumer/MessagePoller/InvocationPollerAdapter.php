<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer\MessagePoller;

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

    public function receiveWithTimeout(int $timeoutInMilliseconds = 0): ?Message
    {
        $result = $this->serviceToCall->{$this->methodName}();
        if ($result === null) {
            return null;
        }
        return $result instanceof Message
            ? $result
            : MessageBuilder::withPayload($result)->build();
    }
}
