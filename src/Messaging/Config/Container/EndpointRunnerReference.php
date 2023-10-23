<?php

namespace Ecotone\Messaging\Config\Container;

class EndpointRunnerReference extends Reference
{
    public function __construct(string $endpointId)
    {
        parent::__construct('endpointRunner.'.$endpointId);
    }
}
