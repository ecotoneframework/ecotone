<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\PointcutExpression;
use Ecotone\Messaging\Handler\Type\ObjectType;

/**
 * licence Apache-2.0
 */
class PointcutInterfaceExpression implements PointcutExpression
{
    public function __construct(private ObjectType $interfaceType)
    {
    }

    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        return $this->interfaceType->acceptType($interfaceToCall->getInterfaceType());
    }
}
