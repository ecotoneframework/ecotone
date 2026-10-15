<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\FailureHandler;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\QueryHandler;
use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
final class ExampleFailureCommandHandler
{
    private int $calledTimes = 0;
    private bool $wasSuccessful = false;

    #[Asynchronous('async')]
    #[CommandHandler('handler.fail', endpointId: 'failureHandler')]
    public function handle(array $recoverAtAttempt): void
    {
        $recoverAtAttempt = $recoverAtAttempt['command'];
        $this->calledTimes++;
        if ($recoverAtAttempt !== 0 && $this->calledTimes >= $recoverAtAttempt) {
            $this->wasSuccessful = true;
            return;
        }

        throw new InvalidArgumentException('test');
    }

    #[QueryHandler('handler.isSuccessful')]
    public function wasSuccessful(): bool
    {
        return $this->wasSuccessful;
    }
}
