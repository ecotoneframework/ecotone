<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Recoverability;

use Closure;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Throwable;

class RetryRunner
{
    public function __construct(private EcotoneClockInterface $clock, private LoggingGateway $logger)
    {
    }

    public function runWithRetry(Closure $closure, RetryTemplate $retryTemplate, Message $message, string $exceptionClass, string $retryMessage): void
    {
        $retryNumber = 1;
        do {
            try {
                $closure();
                break;
            } catch (Throwable $exception) {
                if (! $exception instanceof $exceptionClass) {
                    throw $exception;
                }

                if (! $retryTemplate->canBeCalledNextTime($retryNumber)) {
                    throw $exception;
                }

                $this->logger->info($retryMessage, $message, ['exception' => $exception]);
                $this->clock->sleep($retryTemplate->durationToNextRetry($retryNumber));
                $retryNumber++;

                if ($retryNumber > $retryTemplate->getMaxAttempts()) {
                    throw $exception;
                }
            }
        } while ($retryNumber <= $retryTemplate->getMaxAttempts());
    }
}
