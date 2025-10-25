<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;

use function is_scalar;

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
    public function __construct(private ?Type $parameterType, private ?ParameterDefaultValue $defaultValue, private string $headerName, private bool $isRequired, private ConversionService $conversionService)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        if (! $message->getHeaders()->containsKey($this->headerName)) {
            if ($this->defaultValue) {
                return $this->defaultValue->getValue();
            }

            if (! $this->isRequired) {
                return;
            }
        }

        $headerValue = $message->getHeaders()->get($this->headerName);

        $targetType = $this->parameterType;

        if (! $targetType->accepts($headerValue)) {
            $sourceValueType = Type::createFromVariable($headerValue);
            $sourceMediaType = MediaType::createApplicationXPHP();
            if ($this->canConvertTo($sourceValueType, $sourceMediaType, $targetType)) {
                $headerValue = $this->doConversion($headerValue, $sourceValueType, $sourceMediaType, $targetType);
            } elseif (is_scalar($headerValue) && $this->canConvertTo($sourceValueType, MediaType::parseMediaType(DefaultHeaderMapper::FALLBACK_HEADER_CONVERSION_MEDIA_TYPE), $targetType)) {
                $headerValue = $this->doConversion($headerValue, $sourceValueType, MediaType::parseMediaType(DefaultHeaderMapper::FALLBACK_HEADER_CONVERSION_MEDIA_TYPE), $targetType);
            } else {
                throw ConversionException::create("Can't convert {$this->headerName} from {$sourceValueType} to {$targetType}. Lack of PHP Converter or JSON Media Type Converter available.");
            }
        }

        return $headerValue;
    }

    private function canConvertTo(Type $sourceType, MediaType $sourceMediaType, Type $targetType): bool
    {
        return $this->conversionService->canConvert(
            $sourceType,
            $sourceMediaType,
            $targetType,
            MediaType::createApplicationXPHP()
        );
    }

    private function doConversion(mixed $headerValue, Type $sourceValueType, MediaType $sourceMediaType, Type $targetType)
    {
        $headerValue = $this->conversionService->convert(
            $headerValue,
            $sourceValueType,
            $sourceMediaType,
            $targetType,
            MediaType::createApplicationXPHP()
        );

        return $headerValue;
    }
}
