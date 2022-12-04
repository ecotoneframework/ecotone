<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config;

final class RegisterLoadAggregateChannel
{
    public function __construct(private string $className)
    {

    }

    public function getClassName(): string
    {
        return $this->className;
    }
}