<?php declare(strict_types=1);


namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;


use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class AroundInterceptorWithCustomParameterConverters
{
    private bool $wasCalled = false;

    #[Around(pointcut: self::class)]
    public function handle(MethodInvocation $methodInvocation, #[Header("token")] int $token, #[Payload] \stdClass $payload, #[Headers] array $headers)
    {
        $this->wasCalled = true;
        return $methodInvocation->proceed();
    }

    public function wasCalled() : bool
    {
        return $this->wasCalled;
    }
}