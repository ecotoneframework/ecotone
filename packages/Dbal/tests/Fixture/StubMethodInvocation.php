<?php

namespace Test\Ecotone\Dbal\Fixture;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use stdClass;

class StubMethodInvocation implements MethodInvocation
{
    private int $calledTimes = 0;
    private $returnData;

    private function __construct($returnData = null)
    {
        $this->returnData = $returnData;
    }

    public static function create(): self
    {
        return new self();
    }

    public function getCalledTimes(): int
    {
        return $this->calledTimes;
    }

    public function proceed()
    {
        $this->calledTimes++;

        return $this->returnData;
    }

    public function getObjectToInvokeOn()
    {
        return new stdClass();
    }

    public function getInterceptedClassName(): string
    {
        return self::class;
    }

    public function getInterceptedMethodName(): string
    {
        return 'getInterceptedInterface';
    }

    public function getInterceptedInterface(): InterfaceToCall
    {
        return InterfaceToCall::create(self::class, 'getInterceptedInterface');
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
    }
}
