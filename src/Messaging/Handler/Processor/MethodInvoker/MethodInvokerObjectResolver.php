<?php

/*
 * licence Apache-2.0
 */

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Message;

interface MethodInvokerObjectResolver
{
    public function resolveFor(Message $message): object|string;
}
