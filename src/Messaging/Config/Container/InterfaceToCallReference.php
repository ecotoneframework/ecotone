<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * licence Apache-2.0
 */
class InterfaceToCallReference extends Reference
{
    public function __construct(private string $className, private string $methodName)
    {
        parent::__construct('interfaceToCall-'.$className.'::'.$methodName);
    }

    public static function fromInstance(InterfaceToCall $interfaceToCall): self
    {
        return new self($interfaceToCall->getInterfaceName(), $interfaceToCall->getMethodName());
    }

    public static function create(string $className, string $methodName): self
    {
        return new self($className, $methodName);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getName(): string
    {
        return $this->className . '::' . $this->methodName;
    }

}
