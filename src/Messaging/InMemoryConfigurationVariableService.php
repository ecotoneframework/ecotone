<?php

namespace Ecotone\Messaging;

use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class InMemoryConfigurationVariableService implements ConfigurationVariableService
{
    private array $variables;

    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    public static function create(array $variables): self
    {
        return new self($variables);
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public function getByName(string $name)
    {
        if (! array_key_exists($name, $this->variables)) {
            throw new InvalidArgumentException("Variable {$name} was not found");
        }

        return $this->variables[$name];
    }

    public function hasName(string $name): bool
    {
        return array_key_exists($name, $this->variables);
    }
}
