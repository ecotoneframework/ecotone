<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

abstract class ConnectionReference
{
    public function __construct(private string $referenceName)
    {

    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    public function __toString(): string
    {
        return $this->referenceName;
    }
}
