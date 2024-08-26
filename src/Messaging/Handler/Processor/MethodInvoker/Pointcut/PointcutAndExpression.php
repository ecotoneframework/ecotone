<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\PointcutExpression;

/**
 * licence Apache-2.0
 */
class PointcutAndExpression implements PointcutExpression
{
    public function __construct(private PointcutExpression $left, private PointcutExpression $right)
    {
    }

    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        return $this->right->doesItCutWith($endpointAnnotations, $interfaceToCall)
            && $this->left->doesItCutWith($endpointAnnotations, $interfaceToCall);
    }
}
