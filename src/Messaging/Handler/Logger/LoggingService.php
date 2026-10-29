<?php

declare(strict_types=0);

namespace Ecotone\Messaging\Handler\Logger;

use function array_merge;

use Ecotone\Messaging\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Class LoggingService
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class LoggingService implements LoggingGateway, SubscribableLoggingGateway
{
    use LoggerTrait;

    /**
     * @var LoggerInterface[] $loggers
     */
    private array $loggers = [];

    public function info(Stringable|string $message, Message|array|null $context = [], array $additionalContext = []): void
    {
        $this->log(LogLevel::INFO, $message, $context ?? [], $additionalContext);
    }

    public function error(Stringable|string $message, Message|array|null $context = [], array $additionalContext = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context ?? [], $additionalContext);
    }

    public function critical(Stringable|string $message, Message|array|null $context = [], array $additionalContext = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context ?? [], $additionalContext);
    }

    public function log($level, Stringable|string $message, Message|array $context = [], array $additionalContext = []): void
    {
        $resultingContext = array_merge(
            $context instanceof Message ? [
                'message_id' => $context->getHeaders()->getMessageId(),
                'correlation_id' => $context->getHeaders()->getCorrelationId(),
                'parent_id' => $context->getHeaders()->getParentId(),
            ] : $context,
            $additionalContext
        );

        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $resultingContext);
        }
    }

    public function registerLogger(?LoggerInterface $logger): void
    {
        if ($logger && ! in_array($logger, $this->loggers)) {
            $this->loggers[] = $logger;
        }
    }
}
