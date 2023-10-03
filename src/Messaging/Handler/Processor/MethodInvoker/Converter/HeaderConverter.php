<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

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
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
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
            if ($sourceValueType->isScalar() && $this->canConvertTo($headerValue, DefaultHeaderMapper::DEFAULT_HEADER_CONVERSION_MEDIA_TYPE, $targetType)) {
                $headerValue = $this->doConversion($headerValue, DefaultHeaderMapper::DEFAULT_HEADER_CONVERSION_MEDIA_TYPE, $targetType);
            } elseif ($this->canConvertTo($headerValue, MediaType::APPLICATION_X_PHP, $targetType)) {
                $headerValue = $this->doConversion($headerValue, MediaType::APPLICATION_X_PHP, $targetType);
            }

            //            @TODO
            //            $fromType = TypeDescriptor::createFromVariable($headerValue);
            //            throw ConversionException::create("Lack of converter available for {$interfaceToCall} with parameter name `{$this->parameter}` to convert it from {$fromType} to {$relatedParameter->getTypeDescriptor()}");
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
