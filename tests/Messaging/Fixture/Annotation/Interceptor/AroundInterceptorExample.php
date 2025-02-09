<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Modelling\Attribute\CommandHandler;
use stdClass;

final class AroundInterceptorExample
{
    public ?object $payload = null;
    public ?string $consumerName = null;

    #[Asynchronous('async')]
    #[CommandHandler(routingKey: 'doSomethingAsync', endpointId: 'doSomethingAsync.endpoint')]
    public function doSomethingAsync(stdClass $command): void
    {

    }

    #[Around(pointcut: AsynchronousRunningEndpoint::class)]
    public function intercept(MethodInvocation $methodInvocation, #[Header('polledChannelName')] string $consumerName, #[Payload] stdClass $command)
    {
        $this->payload = $command;
        $this->consumerName = $consumerName;

        return $methodInvocation->proceed();
    }
}
