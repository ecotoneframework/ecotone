<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\ParameterConverter;

/**
 * licence Apache-2.0
 */
class MethodInvocationConverter implements ParameterConverter
{
    public function getArgumentFrom(Message $message, ?MethodInvocation $methodInvocation = null): mixed
    {
        return $methodInvocation;
    }
}
