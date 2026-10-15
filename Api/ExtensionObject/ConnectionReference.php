<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

/**
 * licence Apache-2.0
 */
abstract class ConnectionReference
{
    public function __construct(private string $referenceName, public readonly ?string $connectionName)
    {
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    public function __toString(): string
    {
        return $this->connectionName;
    }
}
