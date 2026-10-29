<?php

namespace Ecotone\Messaging;

use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * licence Apache-2.0
 */
interface MessagePoller
{
    /**
     * Receive with timeout using polling metadata
     * Tries to receive message till time out passes
     *
     * @param PollingMetadata $pollingMetadata Contains timeout and execution constraints
     */
    public function receiveWithTimeout(PollingMetadata $pollingMetadata): ?Message;

    /**
     * Called when the consumer is about to stop
     * This allows the poller to perform cleanup operations like committing pending messages
     */
    public function onConsumerStop(): void;
}
