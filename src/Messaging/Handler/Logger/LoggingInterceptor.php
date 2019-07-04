<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Handler\Logger;

use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogAfter;
use SimplyCodedSoftware\Messaging\Handler\Logger\Annotation\LogBefore;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class LoggingInterceptor
 * @package SimplyCodedSoftware\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingInterceptor
{
    /**
     * @var LoggingService
     */
    private $loggingService;

    /**
     * LoggingInterceptor constructor.
     * @param LoggingService $loggingService
     */
    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * @param Message $message
     * @param LogBefore $log
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws TypeDefinitionException
     */
    public function logBefore(Message $message, LogBefore $log): void
    {
        $this->loggingService->log(LoggingLevel::create($log->logLevel, $log->logFullMessage), $message);
    }

    /**
     * @param Message $message
     * @param LogAfter $log
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws TypeDefinitionException
     */
    public function logAfter(Message $message, LogAfter $log): void
    {
        $this->loggingService->log(LoggingLevel::create($log->logLevel, $log->logFullMessage), $message);
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param Message $message
     * @param Logger $log
     * @return mixed
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws TypeDefinitionException
     * @throws \Throwable
     */
    public function logException(MethodInvocation $methodInvocation, Message $message, Logger $log)
    {
        try {
            $returnValue = $methodInvocation->proceed();
        }catch (\Throwable $exception) {
            $this->loggingService->logException(LoggingLevel::create($log->logLevel, $log->logFullMessage), $exception, $message);

            throw $exception;
        }

        return $returnValue;
    }
}