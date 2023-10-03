<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;

class MethodInvocationConverter implements ParameterConverter
{
    public function getArgumentFrom(Message $message, ?MethodInvocation $methodInvocation = null): mixed
    {
        return $methodInvocation;
    }
}
