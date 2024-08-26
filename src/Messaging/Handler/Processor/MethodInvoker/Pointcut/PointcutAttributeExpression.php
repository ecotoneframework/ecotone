<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\PointcutExpression;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * licence Apache-2.0
 */
class PointcutAttributeExpression implements PointcutExpression
{
    public function __construct(private TypeDescriptor $typeDescriptor)
    {
    }

    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        foreach ($endpointAnnotations as $endpointAnnotation) {
            $endpointType = TypeDescriptor::createFromVariable($endpointAnnotation);

            if ($endpointType->equals($this->typeDescriptor)) {
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
