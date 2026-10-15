<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Interceptor\MethodInvocation;
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
