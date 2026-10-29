<?php

/*
 * licence Apache-2.0
 */

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Message;

interface AroundInterceptable
{
    public function getMethodName(): string;

    public function getObjectToInvokeOn(Message $message): string|object;

    /**
     * @return mixed[]
     */
    public function getArguments(Message $message): array;
}
