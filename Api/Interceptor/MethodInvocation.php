<?php

declare(strict_types=1);

namespace Ecotone\Api\Interceptor;

/**
 * licence Apache-2.0
 */
interface MethodInvocation
{
    public function proceed(): mixed;

    public function cloneCurrentState(): self;

    public function getObjectToInvokeOn(): string|object;

    public function getMethodName(): string;


    public function getName(): string;

    /**
     * @return mixed[]
     */
    public function getArguments(): array;

}
