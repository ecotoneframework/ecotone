<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Retry;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Interceptor\Precedence;
use RuntimeException;

/**
 * licence Apache-2.0
 */
final class InterceptorAfterRetryHandler
{
    private array $calls = [];
    private int $commandCalled = 0;

    #[CommandHandler('interceptor.after.retry')]
    public function handleCommand(int $stopThrowingAtAttempt): void
    {
        $this->calls[] = 'commandHandler';

        if ($this->commandCalled < $stopThrowingAtAttempt) {
            $this->commandCalled++;
            throw new RuntimeException('test');
        }
    }

    #[Asynchronous('async')]
    #[CommandHandler('interceptor.after.retry.async', endpointId: 'async.interceptor.handler')]
    public function handleAsyncCommand(int $stopThrowingAtAttempt): void
    {
        $this->calls[] = 'asyncCommandHandler';

        if ($this->commandCalled < $stopThrowingAtAttempt) {
            $this->commandCalled++;
            throw new RuntimeException('test');
        }
    }

    #[Around(precedence: Precedence::GLOBAL_INSTANT_RETRY_PRECEDENCE + 1, pointcut: CommandBus::class)]
    public function interceptCommand(MethodInvocation $methodInvocation): mixed
    {
        $this->calls[] = 'interceptor';
        return $methodInvocation->proceed();
    }

    #[Around(precedence: Precedence::GLOBAL_INSTANT_RETRY_PRECEDENCE - 1, pointcut: CommandBus::class)]
    public function interceptCommandPreRetry(MethodInvocation $methodInvocation): mixed
    {
        $this->calls[] = 'preRetryInterceptor';
        return $methodInvocation->proceed();
    }

    #[Around(precedence: Precedence::GLOBAL_INSTANT_RETRY_PRECEDENCE + 1, pointcut: AsynchronousRunningEndpoint::class)]
    public function interceptAsyncCommand(MethodInvocation $methodInvocation): mixed
    {
        $this->calls[] = 'asyncInterceptor';
        return $methodInvocation->proceed();
    }

    #[Around(precedence: Precedence::GLOBAL_INSTANT_RETRY_PRECEDENCE - 1, pointcut: AsynchronousRunningEndpoint::class)]
    public function interceptAsyncCommandPreRetry(MethodInvocation $methodInvocation): mixed
    {
        $this->calls[] = 'asyncPreRetryInterceptor';
        return $methodInvocation->proceed();
    }

    #[QueryHandler('interceptor.getCalls')]
    public function getCalls(): array
    {
        return $this->calls;
    }
}
