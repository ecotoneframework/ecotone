<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class PayloadArgument
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class PayloadConverter implements ParameterConverter
{
    private function __construct(private ConversionService $conversionService, private string $parameterName)
    {
    }

    public static function create(ConversionService $conversionService, string $parameterName): PayloadConverter
    {
        return new self($conversionService, $parameterName);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message)
    {
        $data = $message->getPayload();

        $sourceMediaType = $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)
            ? MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE))
            : MediaType::createApplicationXPHP();
        $parameterMediaType = MediaType::createApplicationXPHP();
        $sourceTypeDescriptor = $sourceMediaType->hasTypeParameter()
            ? TypeDescriptor::create($sourceMediaType->getParameter('type'))
            : TypeDescriptor::createFromVariable($data);

        $parameterType = $relatedParameter->getTypeDescriptor();

        if (! ($sourceTypeDescriptor->isCompatibleWith($parameterType) && ($parameterType->isMessage() || $parameterType->isAnything() || $sourceMediaType->isCompatibleWith($parameterMediaType)))) {
            $convertedData = null;
            if (! $parameterType->isCompoundObjectType() && ! $parameterType->isAbstractClass() && ! $parameterType->isInterface() && ! $parameterType->isAnything() && ! $parameterType->isUnionType() && $this->canConvertParameter(
                $sourceTypeDescriptor,
                $sourceMediaType,
                $parameterType,
                $parameterMediaType
            )) {
                $convertedData = $this->doConversion($interfaceToCall, $relatedParameter, $data, $sourceTypeDescriptor, $sourceMediaType, $parameterType, $parameterMediaType);
            } elseif ($message->getHeaders()->containsKey(MessageHeaders::TYPE_ID)) {
                $resolvedTargetParameterType = TypeDescriptor::create($message->getHeaders()->get(MessageHeaders::TYPE_ID));
                if ($this->canConvertParameter(
                    $sourceTypeDescriptor,
                    $sourceMediaType,
                    $resolvedTargetParameterType,
                    $parameterMediaType
                )
                ) {
                    $convertedData = $this->doConversion($interfaceToCall, $relatedParameter, $data, $sourceTypeDescriptor, $sourceMediaType, $resolvedTargetParameterType, $parameterMediaType);
                }
            }

            if (! is_null($convertedData)) {
                $data = $convertedData;
            } else {
                if (! ($sourceTypeDescriptor->isNullType() && $relatedParameter->doesAllowNulls()) && ! $sourceTypeDescriptor->isCompatibleWith($parameterType)) {
                    if ($parameterType->isUnionType()) {
                        throw InvalidArgumentException::create("Can not call {$interfaceToCall} lack of information which type should be used to deserialization. Consider adding __TYPE__ header to indicate which union type it should be resolved to.");
                    }

                    throw InvalidArgumentException::create("Can not call {$interfaceToCall}. Lack of Media Type Converter for {$sourceMediaType}:{$sourceTypeDescriptor} to {$parameterMediaType}:{$parameterType}");
                }
            }
        }
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() == $this->parameterName;
    }

    private function canConvertParameter(Type $requestType, MediaType $requestMediaType, Type $parameterType, MediaType $parameterMediaType): bool
    {
        return $this->conversionService->canConvert(
            $requestType,
            $requestMediaType,
            $parameterType,
            $parameterMediaType
        );
    }

    /**
     * @param mixed $data
     * @return mixed
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function doConversion(InterfaceToCall $interfaceToCall, InterfaceParameter $interfaceParameterToConvert, $data, Type $requestType, MediaType $requestMediaType, Type $parameterType, MediaType $parameterMediaType): mixed
    {
        try {
            return $this->conversionService->convert(
                $data,
                $requestType,
                $requestMediaType,
                $parameterType,
                $parameterMediaType
            );
        } catch (ConversionException $exception) {
            throw ConversionException::createFromPreviousException("There is a problem with conversion for {$interfaceToCall} on parameter {$interfaceParameterToConvert->getName()}: " . $exception->getMessage(), $exception);
        }
    }
}
