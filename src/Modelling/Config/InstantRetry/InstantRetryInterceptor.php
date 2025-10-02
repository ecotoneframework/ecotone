<?php

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Exception;

/**
 * licence Apache-2.0
 */
class InstantRetryInterceptor
{
    public function __construct(
        private int                $maxRetryAttempts,
        private array              $exceptions,
        private RetryStatusTracker $retryStatusTracker,
        private ?string            $relatedEndpointId = null,
    ) {
    }

    public function retry(MethodInvocation $methodInvocation, Message $message, #[Reference] LoggingGateway $logger, ?PollingMetadata $pollingMetadata)
    {
        if (! is_null($this->relatedEndpointId)) {
            if (! $pollingMetadata || $pollingMetadata->getEndpointId() !== $this->relatedEndpointId) {
                return $methodInvocation->proceed();
            }
        }

        if ($this->retryStatusTracker->isCurrentlyWrappedByRetry()) {
            return $methodInvocation->proceed();
        }

        try {
            $isSuccessful = false;
            $retries = 0;
            $this->retryStatusTracker->markAsWrapped();

            $result = null;
            while (! $isSuccessful) {
                $retryableInvocationState = $methodInvocation->cloneCurrentState();

                try {
                    $result = $methodInvocation->proceed();
                    $isSuccessful = true;
                } catch (Exception $exception) {
                    if (! $this->canRetryThrownException($exception) || $retries >= $this->maxRetryAttempts) {
                        $logger->info(
                            sprintf('Instant retry have exceed %d/%d retry limit. No more retries will be done', $retries, $this->maxRetryAttempts),
                            $message,
                            ['exception' => $exception],
                        );
                        throw $exception;
                    }

                    $retries++;
                    $logger->info(
                        sprintf(
                            'Exception happened. Trying to self-heal by doing instant try %d out of %d. Due to %s',
                            $retries,
                            $this->maxRetryAttempts,
                            $exception->getMessage()
                        ),
                        $message,
                        ['exception' => $exception]
                    );
                    $methodInvocation = $retryableInvocationState;
                }
            }
        } finally {
            $this->retryStatusTracker->markAsUnwrapped();
        }

        return $result;
    }

    private function canRetryThrownException(Exception $thrownException): bool
    {
        if ($this->exceptions === []) {
            return true;
        }

        foreach ($this->exceptions as $exception) {
            if ($thrownException instanceof $exception) {
                return true;
            }
        }

        return false;
    }
}
