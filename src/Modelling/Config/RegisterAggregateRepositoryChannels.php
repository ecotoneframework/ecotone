<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config;

final class RegisterAggregateRepositoryChannels
{
    public function __construct(private string $className, private bool $isEventSourced)
    {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function isEventSourced(): bool
    {
        return $this->isEventSourced;
    }
}
