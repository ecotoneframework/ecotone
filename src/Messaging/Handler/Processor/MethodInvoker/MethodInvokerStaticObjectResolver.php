<?php

/*
 * licence Apache-2.0
 */

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Message;

class MethodInvokerStaticObjectResolver implements MethodInvokerObjectResolver
{
    public function __construct(private object|string $objectToInvokeOn)
    {
    }

    public function resolveFor(Message $message): object|string
    {
        return $this->objectToInvokeOn;
    }
}
