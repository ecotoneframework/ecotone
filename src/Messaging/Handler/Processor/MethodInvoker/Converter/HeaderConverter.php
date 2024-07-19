<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;

/**
 * Class HeaderArgument
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class HeaderConverter implements ParameterConverter
{
    public function __construct(private InterfaceParameter $parameter, private string $headerName, private bool $isRequired, private ConversionService $conversionService)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        if (! $message->getHeaders()->containsKey($this->headerName)) {
            if ($this->parameter->hasDefaultValue()) {
                return $this->parameter->getDefaultValue();
            }

            if (! $this->isRequired) {
                return;
            }
        }

        $headerValue = $message->getHeaders()->get($this->headerName);

        $targetType = $this->parameter->getTypeDescriptor();

        $sourceValueType = TypeDescriptor::createFromVariable($headerValue);
        if (! $sourceValueType->isCompatibleWith($targetType)) {
            if ($this->canConvertTo($headerValue, MediaType::APPLICATION_X_PHP, $targetType)) {
                $headerValue = $this->doConversion($headerValue, MediaType::APPLICATION_X_PHP, $targetType);
            } elseif ($sourceValueType->isScalar() && $this->canConvertTo($headerValue, DefaultHeaderMapper::FALLBACK_HEADER_CONVERSION_MEDIA_TYPE, $targetType)) {
                $headerValue = $this->doConversion($headerValue, DefaultHeaderMapper::FALLBACK_HEADER_CONVERSION_MEDIA_TYPE, $targetType);
            } else {
                throw ConversionException::create("Can't convert {$this->headerName} from {$sourceValueType} to {$targetType}. Lack of PHP Converter or JSON Media Type Converter available.");
            }
        }

        return $headerValue;
    }

    private function canConvertTo(mixed $headerValue, string $sourceMediaType, Type $targetType): bool
    {
        return $this->conversionService->canConvert(
            TypeDescriptor::createFromVariable($headerValue),
            MediaType::parseMediaType($sourceMediaType),
            $targetType,
            MediaType::createApplicationXPHP()
        );
    }

    private function doConversion(mixed $headerValue, string $sourceMediaType, Type $targetType)
    {
        $headerValue = $this->conversionService->convert(
            $headerValue,
            TypeDescriptor::createFromVariable($headerValue),
            MediaType::parseMediaType($sourceMediaType),
            $targetType,
            MediaType::createApplicationXPHP()
        );

        return $headerValue;
    }
}
