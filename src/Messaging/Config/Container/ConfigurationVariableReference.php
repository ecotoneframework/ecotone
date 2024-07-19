<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
class ConfigurationVariableReference extends Reference
{
    public function __construct(private string $variableName, private bool $isRequired = true, private mixed $defaultValue = null)
    {
        parent::__construct('env.'.$variableName);
    }

    /**
     * @return string
     */
    public function getVariableName(): string
    {
        return $this->variableName;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }
}
