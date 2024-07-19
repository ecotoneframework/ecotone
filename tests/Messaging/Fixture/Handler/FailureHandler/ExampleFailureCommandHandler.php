<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\FailureHandler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
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
