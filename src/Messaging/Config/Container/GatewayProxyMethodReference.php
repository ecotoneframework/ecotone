<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
class GatewayProxyMethodReference extends Reference
{
    public function __construct(GatewayProxyReference $gatewayProxyReference, string $methodName)
    {
        parent::__construct("gateway.{$gatewayProxyReference->getReferenceName()}::{$methodName}");
    }
}
