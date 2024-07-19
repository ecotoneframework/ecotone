<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
class ConsoleCommandReference extends Reference
{
    public function __construct(private string $commandName)
    {
        parent::__construct("console.$commandName");
    }
}
