<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;

class MethodInvocationConverter implements ParameterConverter
{
    public function __construct(
        private string $parameterName,
    ) {
    }

    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, ?MethodInvocation $methodInvocation = null)
    {
        return $methodInvocation;
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }
}
