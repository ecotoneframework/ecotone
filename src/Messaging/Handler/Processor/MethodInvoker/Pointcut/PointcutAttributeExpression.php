<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\PointcutExpression;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
class PointcutAttributeExpression implements PointcutExpression
{
    public function __construct(private Type $typeDescriptor)
    {
    }

    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        foreach ($endpointAnnotations as $endpointAnnotation) {
            if ($this->typeDescriptor->accepts($endpointAnnotation)) {
                return true;
            }
        }

        if ($interfaceToCall->hasMethodAnnotation($this->typeDescriptor)
            || $interfaceToCall->hasClassAnnotation($this->typeDescriptor)) {
            return true;
        }

        return false;
    }
}
