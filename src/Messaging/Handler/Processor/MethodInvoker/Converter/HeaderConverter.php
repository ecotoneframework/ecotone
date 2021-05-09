<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;

/**
 * Class HeaderArgument
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class HeaderConverter implements ParameterConverter
{
    private string $headerName;
    private string $parameterName;
    private bool $isRequired;
    private ConversionService $conversionService;

    private function __construct(string $parameterName, string $headerName, bool $isRequired, ConversionService $conversionService)
    {
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
        $this->isRequired = $isRequired;
        $this->conversionService = $conversionService;
    }

    public static function create(string $parameterName, string $headerName, bool $isRequired, ConversionService $conversionService) : self
    {
        return new self($parameterName, $headerName, $isRequired, $conversionService);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations)
    {
        $isRequired = $this->isRequired;
        if (!$message->getHeaders()->containsKey($this->headerName)) {
            if ($relatedParameter->hasDefaultValue()) {
                return $relatedParameter->getDefaultValue();
            }

            if (!$isRequired) {
                return null;
            }
        }

        $headerValue = $message->getHeaders()->get($this->headerName);
        $convertedHeadersToDifferentFormat = $message->getHeaders()->containsKey(DefaultHeaderMapper::CONVERTED_HEADERS_TO_DIFFERENT_FORMAT)
                                    ? \json_decode($message->getHeaders()->get(DefaultHeaderMapper::CONVERTED_HEADERS_TO_DIFFERENT_FORMAT), true, 512, JSON_THROW_ON_ERROR)
                                    : [];

        if (!TypeDescriptor::createFromVariable($headerValue)->isCompatibleWith($relatedParameter->getTypeDescriptor())) {
            if (in_array($this->headerName, $convertedHeadersToDifferentFormat) && $this->canConvertTo($headerValue, DefaultHeaderMapper::DEFAULT_HEADER_CONVERSION_MEDIA_TYPE, $relatedParameter)) {
                $headerValue = $this->doConversion($headerValue, DefaultHeaderMapper::DEFAULT_HEADER_CONVERSION_MEDIA_TYPE, $relatedParameter);
            }else if ($this->canConvertTo($headerValue, MediaType::APPLICATION_X_PHP, $relatedParameter)) {
                $headerValue = $this->doConversion($headerValue, MediaType::APPLICATION_X_PHP, $relatedParameter);
            }
        }

        return $headerValue;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() == $this->parameterName;
    }

    private function canConvertTo(mixed $headerValue, string $sourceMediaType, InterfaceParameter $relatedParameter): bool
    {
        return $this->conversionService->canConvert(
            TypeDescriptor::createFromVariable($headerValue),
            MediaType::parseMediaType($sourceMediaType),
            $relatedParameter->getTypeDescriptor(),
            MediaType::createApplicationXPHP()
        );
    }

    private function doConversion(mixed $headerValue, string $sourceMediaType, InterfaceParameter $relatedParameter)
    {
        $headerValue = $this->conversionService->convert(
            $headerValue,
            TypeDescriptor::createFromVariable($headerValue),
            MediaType::parseMediaType($sourceMediaType),
            $relatedParameter->getTypeDescriptor(),
            MediaType::createApplicationXPHP()
        );

        return $headerValue;
    }
}