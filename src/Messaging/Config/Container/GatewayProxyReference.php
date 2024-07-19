<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
final class GatewayProxyReference extends Reference
{
    public function __construct(
        string $referenceName,
        private string $interfaceName,
    ) {
        parent::__construct($referenceName);
    }

    public function getReferenceName(): string
    {
        return $this->id;
    }

    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }

    public function gatewayReferenceForMethod(string $methodName): GatewayProxyMethodReference
    {
        return new GatewayProxyMethodReference($this, $methodName);
    }
}
