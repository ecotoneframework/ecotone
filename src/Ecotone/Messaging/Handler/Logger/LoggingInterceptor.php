<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Logger\Annotation\LogAfter;
use Ecotone\Messaging\Handler\Logger\Annotation\LogBefore;
use Ecotone\Messaging\Handler\Logger\Annotation\LogError;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class LoggingInterceptor
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingInterceptor
{
    private ?\Ecotone\Messaging\Handler\Logger\LoggingService $loggingService;

    /**
     * LoggingInterceptor constructor.
     * @param LoggingService $loggingService
     */
    public function __construct(?LoggingService $loggingService)
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
     * @param Message          $message
     * @param LogError         $log
     *
     * @return mixed
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws TypeDefinitionException
     * @throws \Throwable
     */
    public function logException(MethodInvocation $methodInvocation, Message $message, LogError $log, ReferenceSearchService $referenceSearchService)
    {
        $loggingService = new LoggingService(
            $referenceSearchService->get(ConversionService::REFERENCE_NAME),
            $referenceSearchService->get(LoggingHandlerBuilder::LOGGER_REFERENCE)
        );

        try {
            $returnValue = $methodInvocation->proceed();
        }catch (\Throwable $exception) {
            $loggingService->logException(LoggingLevel::create($log->logLevel, $log->logFullMessage), $exception, $message);

            throw $exception;
        }

        return $returnValue;
    }
}