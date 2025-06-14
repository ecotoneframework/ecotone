<?php

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;

/**
 * licence Apache-2.0
 */
class ContainerDefinitionsHolder
{
    /**
     * @param array<string, Definition|Reference> $definitions
     * @param ConsoleCommandConfiguration[] $registeredCommands
     */
    public function __construct(private array $definitions, private array $registeredCommands = [])
    {

    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @return ConsoleCommandConfiguration[]
     */
    public function getRegisteredCommands(): array
    {
        return $this->registeredCommands;
    }
}
