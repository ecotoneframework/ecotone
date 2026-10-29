<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Closure;
use Ecotone\Messaging\Handler\InterfaceToCall;
use stdClass;

/**
 * licence Apache-2.0
 */
class StubMethodInvocation implements MethodInvocation
{
    private int $calledTimes = 0;
    private Closure $functionToCall;

    private function __construct(Closure $functionToCall)
    {
        $this->functionToCall = $functionToCall;
    }

    public static function createEndingImmediately(): self
    {
        return new self(function () {
        });
    }

    public function cloneCurrentState(): MethodInvocation
    {
        return clone $this;
    }

    public static function createWithCalledFunction(Closure $functionToCall)
    {
    }

    public function getCalledTimes(): int
    {
        return $this->calledTimes;
    }

    public function proceed(): mixed
    {
        $this->calledTimes++;

        return $this->functionToCall->call($this);
    }

    public function getObjectToInvokeOn(): string|object
    {
        return new stdClass();
    }

    public function getEndpointAnnotations(): iterable
    {
        return [];
    }

    public function getArguments(): array
    {
        return [];
    }

    public function getMethodName(): string
    {
        return 'someMethod';
    }

    public function getName(): string
    {
        return 'stdClass::someMethod';
    }

    public function getInterfaceToCall(): InterfaceToCall
    {
        return InterfaceToCall::create(stdClass::class, 'someMethod');
    }

    public function replaceArgument(string $parameterName, $value): void
    {
    }
}
