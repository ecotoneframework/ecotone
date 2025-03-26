<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Retry;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\CommandBus;
use RuntimeException;

/**
 * licence Apache-2.0
 */
final class RetriedCommandHandler
{
    private int $called = 0;
    private int $outerCalled = 0;

    #[CommandHandler('retried.synchronous')]
    public function handleSynchronousCommandHandler(int $stopThrowingAfterAttempt): void
    {
        $this->called++;

        if ($this->called < $stopThrowingAfterAttempt) {
            throw new RuntimeException('test');
        }
    }

    #[CommandHandler('retried.nested.sync')]
    public function nestedSynchronousCommandHandler(int $stopThrowingAfterAttempt, CommandBus $commandBus): void
    {
        $this->outerCalled++;
        $commandBus->sendWithRouting('retried.synchronous', $stopThrowingAfterAttempt);

        if ($this->outerCalled < $stopThrowingAfterAttempt) {
            throw new RuntimeException('test');
        }
    }

    #[Asynchronous('async')]
    #[CommandHandler('retried.asynchronous', endpointId: 'async.handler')]
    public function handleAsynchronousHandler(int $stopThrowingAfterAttempt): void
    {
        $this->called++;

        if ($this->called < $stopThrowingAfterAttempt) {
            throw new RuntimeException('test');
        }
    }

    #[Asynchronous('async')]
    #[CommandHandler('retried.nested.async', endpointId: 'async.nested')]
    public function nestedAsynchronousCommandHandler(int $stopThrowingAfterAttempt, CommandBus $commandBus): void
    {
        $this->outerCalled++;
        $commandBus->sendWithRouting('retried.synchronous', $stopThrowingAfterAttempt);

        if ($this->outerCalled < $stopThrowingAfterAttempt) {
            throw new RuntimeException('test');
        }
    }

    #[QueryHandler('retried.getCallCount')]
    public function getCallCount(): int
    {
        return $this->called;
    }
}
