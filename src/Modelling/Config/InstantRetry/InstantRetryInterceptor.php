<?php

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Exception;
use Psr\Log\LoggerInterface;

class InstantRetryInterceptor
{
    public function __construct(private int $maxRetryAttempts, private array $exceptions = [])
    {
    }

    public function retry(MethodInvocation $methodInvocation, #[Reference('logger')] LoggerInterface $logger)
    {
        $isSuccessful = false;
        $retries = 0;

        $result = null;
        while (! $isSuccessful) {
            try {
                $result = $methodInvocation->proceed();
                $isSuccessful = true;
            } catch (Exception $exception) {
                if (! $this->canRetryThrownException($exception) || $retries >= $this->maxRetryAttempts) {
                    $logger->info(sprintf('Instant retry have exceed %d/%d retry limit. No more retries will be done', $retries, $this->maxRetryAttempts), [
                        'exception' => $exception->getMessage(),
                    ]);
                    throw $exception;
                }

                $retries++;
                $logger->info(sprintf('Exception happened. Doing instant try %d out of %d.', $retries, $this->maxRetryAttempts), [
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $result;
    }

    private function canRetryThrownException(Exception $thrownException): bool
    {
        if ($this->exceptions === []) {
            return true;
        }

        foreach ($this->exceptions as $exception) {
            if (TypeDescriptor::createFromVariable($thrownException)->isCompatibleWith(TypeDescriptor::create($exception))) {
                return true;
            }
        }

        return false;
    }
}
