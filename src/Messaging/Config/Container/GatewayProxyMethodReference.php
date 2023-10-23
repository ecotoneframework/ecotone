<?php

namespace Ecotone\Messaging\Config\Container;

class GatewayProxyMethodReference extends Reference
{
    public function __construct(GatewayProxyReference $gatewayProxyReference, string $methodName)
    {
        parent::__construct("gateway.{$gatewayProxyReference->getReferenceName()}::{$methodName}");
    }
}
