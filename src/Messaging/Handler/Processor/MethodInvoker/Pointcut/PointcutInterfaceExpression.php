<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\PointcutExpression;

class PointcutInterfaceExpression implements PointcutExpression
{
    public function __construct(private ClassDefinition $classDefinition)
    {
    }

    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        return $interfaceToCall->getInterfaceType()->isCompatibleWith($this->classDefinition->getClassType());
    }
}
