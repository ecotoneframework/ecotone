<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
class EndpointRunnerReference extends Reference
{
    public function __construct(string $endpointId)
    {
        parent::__construct('endpointRunner.'.$endpointId);
    }
}
