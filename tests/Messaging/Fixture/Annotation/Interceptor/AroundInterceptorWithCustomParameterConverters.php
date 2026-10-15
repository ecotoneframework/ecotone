<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Interceptor\MethodInvocation;
use stdClass;

/**
 * licence Apache-2.0
 */
class AroundInterceptorWithCustomParameterConverters
{
    private bool $wasCalled = false;

    #[Around(pointcut: self::class)]
    public function handle(MethodInvocation $methodInvocation, #[Header('token')] int $token, #[Payload] stdClass $payload, #[Headers] array $headers)
    {
        $this->wasCalled = true;
        return $methodInvocation->proceed();
    }

    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}
