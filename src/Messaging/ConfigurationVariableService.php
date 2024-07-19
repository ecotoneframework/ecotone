<?php

namespace Ecotone\Messaging;

use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
interface ConfigurationVariableService
{
    public const REFERENCE_NAME = ConfigurationVariableService::class;

    /**
     * @throws InvalidArgumentException if not found
     */
    public function getByName(string $name);

    public function hasName(string $name): bool;
}
