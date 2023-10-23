<?php

namespace Ecotone\Messaging\Endpoint;

interface EndpointRunner
{
    public function runEndpointWithExecutionPollingMetadata(?ExecutionPollingMetadata $executionPollingMetadata = null): void;
}
