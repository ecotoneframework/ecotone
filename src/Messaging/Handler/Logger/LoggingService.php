<?php

declare(strict_types=0);

namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class LoggingService
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingService
{
    public const CONTEXT_MESSAGE_HEADER = 'ecotone.logging.contextMessage';
    public const CONTEXT_EXCEPTION_HEADER = 'ecotone.logging.exceptionMessage';
    public const CONTEXT_DATA_HEADER = 'ecotone.logging.contextData';
    public const INFO_LOGGING_CHANNEL = 'infoLoggingChannel';
    public const ERROR_LOGGING_CHANNEL = 'errorLoggingChannel';

    private ConversionService $conversionService;
    private LoggerInterface $logger;

    /**
     * LoggingService constructor.
     * @param ConversionService $conversionService
     * @param LoggerInterface $logger
     */
    public function __construct(ConversionService $conversionService, LoggerInterface $logger)
    {
        $this->conversionService = $conversionService;
        $this->logger = $logger;
    }

    #[ServiceActivator(self::INFO_LOGGING_CHANNEL)]
    public function info(
        #[Payload] string $text,
        #[Header(self::CONTEXT_MESSAGE_HEADER)] ?Message $message,
        #[Header(self::CONTEXT_EXCEPTION_HEADER)] ?Throwable $exception,
        #[Header(self::CONTEXT_DATA_HEADER)] array $contextData,
    ): void {
        if ($message === null) {
            $this->logger->info($text, $contextData);

            return;
        }

        $this->logger->info(
            $text,
            array_merge($contextData, [
                'message_id' => $message->getHeaders()->getMessageId(),
                'correlation_id' => $message->getHeaders()->getCorrelationId(),
                'parent_id' => $message->getHeaders()->getParentId(),
                'headers' => (string)$message->getHeaders(),
                'exception' => $exception,
            ])
        );
    }

    #[ServiceActivator(self::ERROR_LOGGING_CHANNEL)]
    public function error(
        #[Payload] string $text,
        #[Header(self::CONTEXT_MESSAGE_HEADER)] Message $message,
        #[Header(self::CONTEXT_EXCEPTION_HEADER)] ?Throwable $exception,
        #[Header(self::CONTEXT_DATA_HEADER)] array $contextData,
    ): void {
        $this->logger->critical(
            $text,
            array_merge($contextData, [
                'message_id' => $message->getHeaders()->getMessageId(),
                'correlation_id' => $message->getHeaders()->getCorrelationId(),
                'parent_id' => $message->getHeaders()->getParentId(),
                'headers' => (string)$message->getHeaders(),
                'exception' => $exception,
            ])
        );
    }

    /**
     * @param LoggingLevel $loggingLevel
     * @param Message $message
     * @throws InvalidArgumentException
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function log(LoggingLevel $loggingLevel, Message $message): void
    {
        $payload = $this->convertPayloadToScalarType($message);

        if ($loggingLevel->isFullMessageLog()) {
            $this->logger->{$loggingLevel->getLevel()}($payload, ['headers' => (string)$message->getHeaders()]);
            return;
        }

        $this->logger->{$loggingLevel->getLevel()}($payload);
    }

    /**
     * @param LoggingLevel $loggingLevel
     * @param Throwable $exception
     * @param Message $message
     * @throws InvalidArgumentException
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function logException(LoggingLevel $loggingLevel, Throwable $exception, Message $message): void
    {
        $context = ['payload' => $this->convertPayloadToScalarType($message), 'exception' => $exception];

        if ($loggingLevel->isFullMessageLog()) {
            $context = array_merge(['headers' => (string)$message->getHeaders()], $context);
        }

        $this->logger->{$loggingLevel->getLevel()}($exception->getMessage(), $context);
    }

    /**
     * @param Message $message
     * @throws InvalidArgumentException
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    private function convertPayloadToScalarType(Message $message): string
    {
        $data = $message->getPayload();
        $sourceMediaType = $message->getHeaders()->hasContentType() ? $message->getHeaders()->getContentType() : MediaType::createApplicationXPHP();
        $sourceTypeDescriptor = $sourceMediaType->hasTypeParameter() ? $sourceMediaType->getTypeParameter() : TypeDescriptor::createFromVariable($message->getPayload());

        if (is_object($data) && method_exists($data, '__toString')) {
            $data = (string)$data;
        } elseif (! TypeDescriptor::createFromVariable($data)->isScalar()) {
            if ($this->conversionService->canConvert($sourceTypeDescriptor, $sourceMediaType, TypeDescriptor::createStringType(), MediaType::createApplicationJson())) {
                $data = $this->conversionService->convert($data, $sourceTypeDescriptor, $sourceMediaType, TypeDescriptor::createStringType(), MediaType::createApplicationJson());
            } else {
                $data = $this->conversionService->convert($data, $sourceTypeDescriptor, $sourceMediaType, TypeDescriptor::createStringType(), MediaType::createApplicationXPHPSerialized());
            }
        }

        return $data;
    }
}
