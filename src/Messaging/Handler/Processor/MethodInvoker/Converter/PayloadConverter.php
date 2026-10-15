<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Projecting\ProjectingHeaders;
use ReflectionClass;

/**
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class PayloadConverter implements ParameterConverter
{
    public function __construct(private ConversionService $conversionService, private EventMapper $mapper, private string $interfaceName, private string $parameterName, private Type $targetType)
    {
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

        $parameterType = $this->targetType;

        if (
            $parameterType->accepts($data)
            && (
                $parameterType->isMessage()
                || $parameterType->isAnything()
                || $sourceMediaType->isCompatibleWith($parameterMediaType)
            )
        ) {
            return $data;
        }

        $sourceTypeDescriptor = $sourceMediaType->hasTypeParameter()
            ? Type::create($sourceMediaType->getParameter('type'))
            : Type::createFromVariable($data);

        $convertedData = null;
        $concreteTypeFromHeader = $this->concreteTypeFromHeaderFor($message, $parameterType);
        if ($concreteTypeFromHeader !== null && $this->canConvertParameter($sourceTypeDescriptor, $sourceMediaType, $concreteTypeFromHeader, $parameterMediaType)) {
            $convertedData = $this->doConversion($data, $sourceTypeDescriptor, $sourceMediaType, $concreteTypeFromHeader, $parameterMediaType);
        } elseif (! $parameterType->isCompoundObjectType() && ! $parameterType->isAnything() && $this->canConvertParameter(
            $sourceTypeDescriptor,
            $sourceMediaType,
            $parameterType,
            $parameterMediaType
        )) {
            $convertedData = $this->doConversion($data, $sourceTypeDescriptor, $sourceMediaType, $parameterType, $parameterMediaType);
        } elseif ($message->getHeaders()->containsKey(MessageHeaders::TYPE_ID)) {
            $typeHeader = $message->getHeaders()->get(MessageHeaders::TYPE_ID);
            $resolvedTargetParameterType = Type::create($this->mapper?->mapNameToEventType($typeHeader) ?? $typeHeader);
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

                throw InvalidArgumentException::create("Can not call {$this->interfaceName}. Lack of Media Type Converter for {$sourceMediaType}:{$sourceTypeDescriptor} to {$parameterMediaType}:{$parameterType}" . $this->hintForNotInstantiableParameterType($parameterType));
            }
        }
        return $data;
    }

    private function concreteTypeFromHeaderFor(Message $message, Type $parameterType): ?Type
    {
        $typeHeaderName = $message->getHeaders()->containsKey(MessageHeaders::TYPE_ID) ? MessageHeaders::TYPE_ID : ProjectingHeaders::PROJECTION_EVENT_NAME;
        if (! $message->getHeaders()->containsKey($typeHeaderName)) {
            return null;
        }

        if (! $this->isInterfaceOrAbstractClass($parameterType)) {
            return null;
        }
        $parameterClass = $parameterType->toString();

        $typeHeader = $message->getHeaders()->get($typeHeaderName);
        $concreteClass = $this->mapper?->mapNameToEventType($typeHeader) ?? $typeHeader;
        if (! class_exists($concreteClass) || ! is_a($concreteClass, $parameterClass, true)) {
            return null;
        }

        return Type::object($concreteClass);
    }

    private function hintForNotInstantiableParameterType(Type $parameterType): string
    {
        if (! $this->isInterfaceOrAbstractClass($parameterType)) {
            return '';
        }

        return ". Parameter \${$this->parameterName} is typed with {$parameterType->toString()}, which cannot be instantiated, and the message does not name a concrete class in its __TypeId__ header. Type the parameter with a concrete class or a union of concrete classes, or send an object instead of an array. If \${$this->parameterName} is a service rather than the message payload, mark it with #[Reference].";
    }

    private function isInterfaceOrAbstractClass(Type $type): bool
    {
        if (! $type->isClassOrInterface()) {
            return false;
        }

        $className = $type->toString();

        return interface_exists($className) || (class_exists($className) && (new ReflectionClass($className))->isAbstract());
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
            throw ConversionException::createFromPreviousException("There is a problem with conversion for {$this->interfaceName} on parameter {$this->parameterName}: " . $exception->getMessage() . $this->hintForNotInstantiableParameterType($parameterType), $exception);
        }
    }
}
