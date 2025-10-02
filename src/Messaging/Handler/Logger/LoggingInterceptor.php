<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Logger\Annotation\LogError;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Psr\Log\LogLevel;
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
    public function __construct(private LoggingGateway $loggingGateway, private ConversionService $conversionService)
    {
    }

    public function log(Message $message, Logger $logAnnotation): void
    {
        $payload = $this->convertPayloadToScalarType($message);

        $this->loggingGateway->log($logAnnotation->logLevel, $payload, $logAnnotation->logFullMessage ? ['headers' => (string)$message->getHeaders()] : []);
    }

    public function logException(MethodInvocation $methodInvocation, Message $message, ?LogError $logAnnotation)
    {
        try {
            $returnValue = $methodInvocation->proceed();
        } catch (Throwable $exception) {
            $context = ['payload' => $this->convertPayloadToScalarType($message), 'exception' => $exception];

            if ($logAnnotation?->isLogFullMessage()) {
                $context['headers'] = (string)$message->getHeaders();
            }

            $this->loggingGateway->log($logAnnotation?->logLevel ?? LogLevel::CRITICAL, $exception->getMessage(), $context);

            throw $exception;
        }

        return $returnValue;
    }

    private function convertPayloadToScalarType(Message $message): string
    {
        $data = $message->getPayload();
        $sourceMediaType = $message->getHeaders()->hasContentType() ? $message->getHeaders()->getContentType() : MediaType::createApplicationXPHP();
        $sourceTypeDescriptor = $sourceMediaType->hasTypeParameter() ? $sourceMediaType->getTypeParameter() : Type::createFromVariable($message->getPayload());

        if (is_object($data) && method_exists($data, '__toString')) {
            $data = (string)$data;
        } elseif (! Type::createFromVariable($data)->isScalar()) {
            if ($this->conversionService->canConvert($sourceTypeDescriptor, $sourceMediaType, Type::string(), MediaType::createApplicationJson())) {
                $data = $this->conversionService->convert($data, $sourceTypeDescriptor, $sourceMediaType, Type::string(), MediaType::createApplicationJson());
            } else {
                $data = $this->conversionService->convert($data, $sourceTypeDescriptor, $sourceMediaType, Type::string(), MediaType::createApplicationXPHPSerialized());
            }
        }

        return (string) $data;
    }
}
