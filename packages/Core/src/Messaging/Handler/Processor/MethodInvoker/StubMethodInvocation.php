<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;

class StubMethodInvocation implements MethodInvocation
{
    private int $calledTimes = 0;
    private \Closure $functionToCall;

    private function __construct(\Closure $functionToCall)
    {
        $this->functionToCall = $functionToCall;
    }

    public static function createEndingImmediately() : self
    {
        return new self(function(){});
    }

    public static function createWithCalledFunction(\Closure $functionToCall)
    {

    }

    public function getCalledTimes(): int
    {
        return $this->calledTimes;
    }

    public function proceed()
    {
        $this->calledTimes++;

        return $this->functionToCall->call($this);
    }

    public function getObjectToInvokeOn()
    {
        return new \stdClass();
    }

    public function getInterceptedClassName(): string
    {
        return self::class;
    }

    public function getInterceptedMethodName(): string
    {
        return "getInterceptedInterface";
    }

    public function getInterceptedInterface(): InterfaceToCall
    {
        return InterfaceToCall::create(self::class, "getInterceptedInterface");
    }

    public function getEndpointAnnotations(): iterable
    {
        return [];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function replaceArgument(string $parameterName, $value): void
    {
        return;
    }
}