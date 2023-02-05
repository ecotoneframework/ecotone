<?php

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Exception;

class InstantRetryInterceptor
{
    public function __construct(private int $maxRetryAttempts, private array $exceptions = [])
    {
    }

    public function retry(MethodInvocation $methodInvocation)
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
                    throw $exception;
                }

                $retries++;
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
