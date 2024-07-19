<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * licence Apache-2.0
 */
class InterfaceParameterReference extends Reference
{
    public function __construct(private string $className, private string $methodName, private string $parameterName)
    {
        parent::__construct('interfaceParameter-' . $className . '::' . $methodName . '::$' . $parameterName);
    }

    public static function fromInstance(InterfaceToCall $interfaceToCall, string $interfaceParameter): self
    {
        return new self($interfaceToCall->getInterfaceName(), $interfaceToCall->getMethodName(), $interfaceParameter);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function interfaceToCallReference(): InterfaceToCallReference
    {
        return new InterfaceToCallReference($this->className, $this->methodName);
    }


}
