<?php

namespace Ecotone\Api\Messaging;

use Ecotone\Api\ExtensionObject\PollingMetadata;

/**
 * licence Apache-2.0
 */
interface MessagePoller
{
    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message;

    public function onConsumerStop(): void;
}
