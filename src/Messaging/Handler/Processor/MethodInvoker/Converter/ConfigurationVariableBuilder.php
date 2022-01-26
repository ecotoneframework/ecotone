<?php


namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;


use Ecotone\Messaging\Attribute\Parameter\ConfigurationVariable;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Support\InvalidArgumentException;

class ConfigurationVariableBuilder implements ParameterConverterBuilder
{
    private function __construct(private string $parameterName, private string $variableName, private bool $isRequired, private mixed $defaultValue) {}

    public static function create(string $parameterName, string $variableName, bool $isRequired, mixed $defaultValue) : self
    {
        return new self($parameterName, $variableName, $isRequired, $defaultValue);
    }

    public static function createFrom(?string $variableName, InterfaceParameter $interfaceParameter) : self
    {
        return new self($interfaceParameter->getName(), $variableName ?: $interfaceParameter->getName(), !$interfaceParameter->doesAllowNulls() && !$interfaceParameter->hasDefaultValue(), $interfaceParameter->hasDefaultValue() ? $interfaceParameter->getDefaultValue() : null);
    }

    public function getRequiredReferences(): array
    {
        return [ConfigurationVariableService::REFERENCE_NAME];
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $this->parameterName === $parameter->getName();
    }

    public function build(ReferenceSearchService $referenceSearchService): ParameterConverter
    {
        /** @var ConfigurationVariableService $configurationVariableService */
        $configurationVariableService = $referenceSearchService->get(ConfigurationVariableService::REFERENCE_NAME);

        $variableValue = null;
        if ($configurationVariableService->hasName($this->variableName)) {
            $variableValue = $configurationVariableService->getByName($this->variableName);
        }elseif (!$this->isRequired) {
            $variableValue = $this->defaultValue;
        }else {
            throw InvalidArgumentException::create("Trying to access configuration variable `" . $this->variableName . "` however it's missing");
        }

        return new ConfigurationVariableConverter($this->parameterName, $variableValue);
    }
}