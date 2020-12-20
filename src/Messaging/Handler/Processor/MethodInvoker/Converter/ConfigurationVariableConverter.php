<?php


namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;


use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;

class ConfigurationVariableConverter implements ParameterConverter
{
    private string $parameterName;
    private mixed $variableValue;

    public function __construct(string $parameterName, mixed $variableValue)
    {
        $this->parameterName = $parameterName;
        $this->variableValue = $variableValue;
    }

    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations)
    {
        return $this->variableValue;
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $this->parameterName === $parameter->getName();
    }
}