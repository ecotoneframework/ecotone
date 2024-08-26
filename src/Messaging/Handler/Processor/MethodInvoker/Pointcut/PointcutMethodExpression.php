<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\PointcutExpression;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * licence Apache-2.0
 */
class PointcutMethodExpression implements PointcutExpression
{
    private Type $classTypeDescriptor;
    public function __construct(string $class, private string $method)
    {
        $this->classTypeDescriptor = TypeDescriptor::create($class);
    }

    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        return $interfaceToCall->getInterfaceType()->isCompatibleWith($this->classTypeDescriptor) && $interfaceToCall->hasMethodName($this->method);
    }
}
