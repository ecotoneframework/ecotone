<?php

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Exception;

/**
 * licence Apache-2.0
 */
class InstantRetryInterceptor implements DefinedObject
{
    public function __construct(private int $maxRetryAttempts, private array $exceptions = [])
    {
    }

    public function retry(MethodInvocation $methodInvocation, Message $message, #[Reference] LoggingGateway $logger)
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

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->maxRetryAttempts, $this->exceptions]);
    }
}
