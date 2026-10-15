<?php

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;

/**
 * licence Apache-2.0
 */
interface EndpointRunner
{
    public function runEndpointWithExecutionPollingMetadata(?ExecutionPollingMetadata $executionPollingMetadata = null): void;
}
