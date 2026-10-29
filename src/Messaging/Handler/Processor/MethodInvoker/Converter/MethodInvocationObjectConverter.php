<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;

use function is_string;

/**
 * licence Apache-2.0
 */
class MethodInvocationObjectConverter implements ParameterConverter
{
    public function getArgumentFrom(Message $message, ?MethodInvocation $methodInvocation = null)
    {
        $object = $methodInvocation?->getObjectToInvokeOn();
        if (is_string($object)) {
            return null;
        }
        return $object;
    }
}
