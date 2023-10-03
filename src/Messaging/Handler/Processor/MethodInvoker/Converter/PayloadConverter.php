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
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class PayloadConverter implements ParameterConverter
{
    private function __construct(private ConversionService $conversionService, private string $interfaceName, private string $parameterName, private Type $targetType)
    {
    }

    public static function create(ConversionService $conversionService, InterfaceParameter $interfaceParameter): PayloadConverter
    {
        return new self($conversionService, '', $interfaceParameter->getName(), $interfaceParameter->getTypeDescriptor());
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message): mixed
    {
        $data = $message->getPayload();

        $sourceMediaType = $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)
            ? MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE))
            : MediaType::createApplicationXPHP();
        $parameterMediaType = MediaType::createApplicationXPHP();
        $sourceTypeDescriptor = $sourceMediaType->hasTypeParameter()
            ? TypeDescriptor::create($sourceMediaType->getParameter('type'))
            : TypeDescriptor::createFromVariable($data);

        $parameterType = $this->targetType;

        if (! ($sourceTypeDescriptor->isCompatibleWith($parameterType) && ($parameterType->isMessage() || $parameterType->isAnything() || $sourceMediaType->isCompatibleWith($parameterMediaType)))) {
            $convertedData = null;
            if (! $parameterType->isCompoundObjectType() && ! $parameterType->isAbstractClass() && ! $parameterType->isInterface() && ! $parameterType->isAnything() && ! $parameterType->isUnionType() && $this->canConvertParameter(
                $sourceTypeDescriptor,
                $sourceMediaType,
                $parameterType,
                $parameterMediaType
            )) {
                $convertedData = $this->doConversion($data, $sourceTypeDescriptor, $sourceMediaType, $parameterType, $parameterMediaType);
            } elseif ($message->getHeaders()->containsKey(MessageHeaders::TYPE_ID)) {
                $resolvedTargetParameterType = TypeDescriptor::create($message->getHeaders()->get(MessageHeaders::TYPE_ID));
                if ($this->canConvertParameter(
                    $sourceTypeDescriptor,
                    $sourceMediaType,
                    $resolvedTargetParameterType,
                    $parameterMediaType
                )
                ) {
                    $convertedData = $this->doConversion($data, $sourceTypeDescriptor, $sourceMediaType, $resolvedTargetParameterType, $parameterMediaType);
                }
            }

            if (! is_null($convertedData)) {
                $data = $convertedData;
            } else {
                if (! ($sourceTypeDescriptor->isNullType()) && ! $sourceTypeDescriptor->isCompatibleWith($parameterType)) {
                    if ($parameterType->isUnionType()) {
                        throw InvalidArgumentException::create("Can not call {$this->interfaceName} lack of information which type should be used to deserialization. Consider adding __TYPE__ header to indicate which union type it should be resolved to.");
                    }

                    throw InvalidArgumentException::create("Can not call {$this->interfaceName}. Lack of Media Type Converter for {$sourceMediaType}:{$sourceTypeDescriptor} to {$parameterMediaType}:{$parameterType}");
                }
            }
        }
        return $data;
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
    private function doConversion($data, Type $requestType, MediaType $requestMediaType, Type $parameterType, MediaType $parameterMediaType): mixed
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
            throw ConversionException::createFromPreviousException("There is a problem with conversion for {$this->interfaceName} on parameter {$this->parameterName}: " . $exception->getMessage(), $exception);
        }
    }
}
