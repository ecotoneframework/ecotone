<?php

namespace Ecotone\Messaging\Endpoint;

/**
 * licence Apache-2.0
 */
interface EndpointRunner
{
    public function runEndpointWithExecutionPollingMetadata(?ExecutionPollingMetadata $executionPollingMetadata = null): void;
}
