<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodCall;
use Ecotone\Messaging\Message;

/**
 * Interface MessageProcessor
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageProcessor
{
    /**
     * @param Message $message
     * @return mixed can return everything from null to object, string etc.
     */
    public function executeEndpoint(Message $message);

    public function getMethodCall(Message $message): MethodCall;

    /**
     * @return AroundMethodInterceptor[]
     */
    public function getAroundMethodInterceptors(): array;

    public function getObjectToInvokeOn(): string|object;

    public function getInterceptedInterface(): InterfaceToCall;

    /**
     * @return object[]
     */
    public function getEndpointAnnotations(): array;
}
