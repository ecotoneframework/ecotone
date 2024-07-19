<?php

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\Container\ContainerBuilder;

/**
 * licence Apache-2.0
 */
class ContainerDefinitionsHolder implements ContainerImplementation
{
    private array $definitions = [];

    /**
     * @param ConsoleCommandConfiguration[] $registeredCommands
     */
    public function __construct(private array $registeredCommands)
    {

    }

    public function process(ContainerBuilder $builder): void
    {
        $this->definitions = $builder->getDefinitions();
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
