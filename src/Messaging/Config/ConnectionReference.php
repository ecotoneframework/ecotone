<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

/**
 * licence Apache-2.0
 */
abstract class ConnectionReference
{
    public function __construct(private string $referenceName, private ?string $connectionName)
    {

    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    public function __toString(): string
    {
        return $this->connectionName;
    }
}
