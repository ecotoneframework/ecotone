<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
class PollingMetadataReference extends Reference
{
    public function __construct(private string $endpointId)
    {
        parent::__construct("pollingMetadata.{$endpointId}");
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }
}
