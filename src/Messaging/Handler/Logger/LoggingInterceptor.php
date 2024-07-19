<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Handler\Logger\Annotation\LogAfter;
use Ecotone\Messaging\Handler\Logger\Annotation\LogBefore;
use Ecotone\Messaging\Handler\Logger\Annotation\LogError;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Throwable;

/**
 * Class LoggingInterceptor
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class LoggingInterceptor
{
    public function __construct(private LoggingService $loggingService)
    {
    }

    /**
     * @param Message $message
     * @param LogBefore $log
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws TypeDefinitionException
     */
    public function logBefore(Message $message, ?LogBefore $log): void
    {
        $log ??= new LogBefore();

        $this->loggingService->log(LoggingLevel::create($log->logLevel, $log->logFullMessage), $message);
    }

    /**
     * @param Message $message
     * @param LogAfter $log
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws TypeDefinitionException
     */
    public function logAfter(Message $message, ?LogAfter $log): void
    {
        $log ??= new LogAfter();

        $this->loggingService->log(LoggingLevel::create($log->logLevel, $log->logFullMessage), $message);
    }

    public function logException(MethodInvocation $methodInvocation, Message $message, ?LogError $log)
    {
        $log ??= new LogError();

        try {
            $returnValue = $methodInvocation->proceed();
        } catch (Throwable $exception) {
            $this->loggingService->logException(LoggingLevel::create($log->logLevel, $log->logFullMessage), $exception, $message);

            throw $exception;
        }

        return $returnValue;
    }
}
