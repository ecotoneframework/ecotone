<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Psr\Log\LoggerInterface;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Throwable;

/**
 * Class LoggingService
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingService
{
    private \Ecotone\Messaging\Conversion\ConversionService $conversionService;
    private \Psr\Log\LoggerInterface $logger;

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
            $this->logger->{$loggingLevel->getLevel()}($payload, ["headers" => (string)$message->getHeaders()]);
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
        $context = ["payload" => $this->convertPayloadToScalarType($message), "exception" => $exception];

        if ($loggingLevel->isFullMessageLog()) {
            $context = array_merge(["headers" => (string)$message->getHeaders()], $context);
        }

        $this->logger->{$loggingLevel->getLevel()}($exception->getMessage(), $context);
    }

    /**
     * @param Message $message
     * @return mixed|string
     * @throws InvalidArgumentException
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    private function convertPayloadToScalarType(Message $message)
    {
        $data = $message->getPayload();
        $sourceMediaType = $message->getHeaders()->hasContentType() ? $message->getHeaders()->getContentType() : MediaType::createApplicationXPHP();
        $sourceTypeDescriptor = $sourceMediaType->hasTypeParameter() ? $sourceMediaType->getTypeParameter() : TypeDescriptor::createFromVariable($message->getPayload());

        if (is_object($data) && method_exists($data, "__toString")) {
            $data = (string)$data;
        } else if (!TypeDescriptor::createFromVariable($data)->isScalar()) {
            if ($this->conversionService->canConvert($sourceTypeDescriptor, $sourceMediaType, TypeDescriptor::createStringType(), MediaType::createApplicationJson())) {
                $data = $this->conversionService->convert($data, $sourceTypeDescriptor, $sourceMediaType, TypeDescriptor::createStringType(), MediaType::createApplicationJson());
            } else {
                $data = $this->conversionService->convert($data, $sourceTypeDescriptor, $sourceMediaType, TypeDescriptor::createStringType(), MediaType::createApplicationXPHPSerialized());
            }
        }

        return $data;
    }
}