<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

final class GatewayReference
{
    public function __construct(
        private string $referenceName,
        private string $interfaceName,
    ) {
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }
}
