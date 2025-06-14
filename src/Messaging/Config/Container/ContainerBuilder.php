<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Compiler\CompilerPass;
use Ecotone\Messaging\Config\Container\Compiler\ContainerDefinitionsHolder;
use Ecotone\Messaging\Config\DefinedObjectWrapper;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class ContainerBuilder
{
    /**
     * @var array<string, Definition|Reference> $definitions
     */
    private array $definitions = [];

    /**
     * @var array<string, Reference> $externalReferences
     */
    private array $externalReferences = [];

    /**
     * @var CompilerPass[] $compilerPasses
     */
    private array $compilerPasses = [];

    private ?Configuration $configuration = null;

    public function __construct()
    {
    }

    public function register(string $id, DefinedObject|Definition|Reference $definition): Reference
    {
        if (isset($this->definitions[$id])) {
            throw InvalidArgumentException::create("Definition with id {$id} already exists");
        }
        return $this->replace($id, $definition);
    }

    public function replace(string $id, DefinedObject|Definition|Reference $definition): Reference
    {
        if (isset($this->externalReferences[$id])) {
            unset($this->externalReferences[$id]);
        }
        if ($definition instanceof DefinedObject) {
            $definition = new DefinedObjectWrapper($definition);
        }
        $this->definitions[$id] = $definition;
        return new Reference($id);
    }

    public function getDefinition(string $id): Definition|Reference
    {
        return $this->definitions[$id] ?? throw InvalidArgumentException::create("Definition with id {$id} not found");
    }

    /**
     * @return array<string, Definition|Reference>
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<string, Reference>
     */
    public function getExternalReferences(): array
    {
        return $this->externalReferences;
    }

    public function compile(): ContainerDefinitionsHolder
    {
        foreach ($this->compilerPasses as $compilerPass) {
            $compilerPass->process($this);
        }

        return new ContainerDefinitionsHolder(
            $this->getDefinitions(),
            $this->configuration ? $this->configuration->getRegisteredConsoleCommands() : [],
        );
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    public function addCompilerPass(CompilerPass $compilerPass)
    {
        $this->compilerPasses[] = $compilerPass;

        if ($compilerPass instanceof Configuration) {
            $this->configuration = $compilerPass;
        }
    }
}
