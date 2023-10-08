<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Interface MethodInvocation
 * @package Ecotone\Messaging\MethodInterceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MethodInvocation
{
    /**
     * Proceed with invocation. Returns value of invoked method
     */
    public function proceed(): mixed;

    public function getObjectToInvokeOn(): string|object;

    public function getMethodName(): string;

    public function getInterfaceToCall(): InterfaceToCall;

    /**
     * @return mixed[]
     */
    public function getArguments(): array;

    /**
     * @TODO remove in Ecotone 2.0
     * @deprecated Do not use. Will be removed in Ecotone 2.0
     * @param string $parameterName
     * @param mixed $value
     * @return void
     */
    public function replaceArgument(string $parameterName, $value): void;
}
