<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\PointcutExpression;

/**
 * licence Apache-2.0
 */
class PointcutNotExpression implements PointcutExpression
{
    public function __construct(private PointcutExpression $expression)
    {
    }

    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        return ! $this->expression->doesItCutWith($endpointAnnotations, $interfaceToCall);
    }
}
