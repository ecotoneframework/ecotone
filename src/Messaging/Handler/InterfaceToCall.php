<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Future;
use Ecotone\Messaging\Handler\Type\ObjectType;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Attribute\Aggregate;
use ReflectionClass;
use ReflectionException;

/**
 * Class InterfaceToCall
 * @package Ecotone\Messaging\Handler\Gateway\Gateway
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class InterfaceToCall
{
    /**
     * @param object[]        $classAnnotations
     * @param object[]        $methodAnnotations
     * @param InterfaceParameter[] $parameters
     */
    public function __construct(private string $interfaceName, private string $methodName, private array $classAnnotations, private array $methodAnnotations, private iterable $parameters, private ?Type $returnType, private bool $doesReturnTypeAllowNulls, private bool $isStaticallyCalled)
    {
    }

    public static function create(string|object $interfaceOrObjectName, string $methodName): self
    {
        $interface = $interfaceOrObjectName;
        if (is_object($interfaceOrObjectName)) {
            $interface = get_class($interfaceOrObjectName);
        }
        $annotationParser = InMemoryAnnotationFinder::createFrom([$interface]);
        return self::createWithAnnotationFinder($interface, $methodName, $annotationParser);
    }

    public static function createWithAnnotationFinder(string|object $interfaceOrObjectName, string $methodName, AnnotationResolver $annotationParser): self
    {
        $interfaceName = $interfaceOrObjectName;
        if (is_object($interfaceOrObjectName)) {
            $interfaceName = get_class($interfaceOrObjectName);
        }

        $methodAnnotations = $annotationParser->getAnnotationsForMethod($interfaceName, $methodName);
        $classAnnotations = $annotationParser->getAnnotationsForClass($interfaceName);

        try {
            $typeResolver        = TypeResolver::createWithAnnotationParser($annotationParser);
            try {
                $reflectionClass = new ReflectionClass($interfaceName);
                $reflectionMethod = $reflectionClass->getMethod($methodName);
            } catch (ReflectionException) {
                throw InvalidArgumentException::create("Interface {$interfaceName} has no method named {$methodName}");
            }

            $parameters = $typeResolver->getMethodParameters($reflectionClass, $methodName);
            $returnType = $typeResolver->getReturnType($reflectionClass, $methodName);

            $doesReturnTypeAllowNulls = $reflectionMethod->getReturnType() ? $reflectionMethod->getReturnType()->allowsNull() : true;
            $isStaticallyCalled       = $reflectionMethod->isStatic();
        } catch (TypeDefinitionException $definitionException) {
            throw InvalidArgumentException::create("Interface {$interfaceName} has problem with type declaration. {$definitionException->getMessage()}");
        }

        return new self($interfaceName, $methodName, $classAnnotations, $methodAnnotations, $parameters, $returnType, $doesReturnTypeAllowNulls, $isStaticallyCalled);
    }

    public function getInterfaceName(): ?string
    {
        return $this->interfaceName;
    }

    /**
     * @return object[]
     */
    public function getMethodAnnotations(): array
    {
        return $this->methodAnnotations;
    }

    /**
     * @return object[]
     */
    public function getClassAnnotations(): iterable
    {
        return $this->classAnnotations;
    }

    /**
     * @param ObjectType|class-string $className
     *
     * @return bool
     */
    public function hasMethodAnnotation(ObjectType|string $className): bool
    {
        $className = (string) $className;
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if ($methodAnnotation instanceof $className) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ObjectType|class-string $className
     *
     * @return bool
     */
    public function hasClassAnnotation(ObjectType|string $className): bool
    {
        $className = (string) $className;
        foreach ($this->getClassAnnotations() as $classAnnotation) {
            if ($classAnnotation instanceof $className) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Type|class-string $className
     */
    public function hasAnnotation(ObjectType|string $className): bool
    {
        return $this->hasMethodAnnotation($className) || $this->hasClassAnnotation($className);
    }

    /**
     * @return object[]
     */
    public function getAnnotationsByImportanceOrder(ObjectType|string $className): array
    {
        $annotations = [];
        $classNameType = ObjectType::from($className);
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if ($classNameType->accepts($methodAnnotation)) {
                $annotations[] = $methodAnnotation;
            }
        }
        foreach ($this->getClassAnnotations() as $classAnnotation) {
            if ($classNameType->accepts(($classAnnotation))) {
                $annotations[] = $classAnnotation;
            }
        }

        return $annotations;
    }

    public function getSingleClassAnnotationOf(ObjectType|string $className): object
    {
        $classNameType = ObjectType::from($className);
        foreach ($this->getClassAnnotations() as $classAnnotation) {
            if ($classNameType->accepts($classAnnotation)) {
                return $classAnnotation;
            }
        }

        throw InvalidArgumentException::create("Trying to retrieve not existing class annotation {$className} for {$this}");
    }

    /**
     * @return object[]
     */
    public function getClassAnnotationOf(ObjectType|string $className): array
    {
        $annotations = [];
        $classNameType = ObjectType::from($className);
        foreach ($this->getClassAnnotations() as $classAnnotation) {
            if ($classNameType->accepts($classAnnotation)) {
                $annotations[] = $classAnnotation;
            }
        }

        return $annotations;
    }

    /**
     * @throws MessagingException
     */
    public function getSingleMethodAnnotationOf(ObjectType|string $className): object
    {
        $classNameType = ObjectType::from($className);
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if ($classNameType->accepts($methodAnnotation)) {
                return $methodAnnotation;
            }
        }

        throw InvalidArgumentException::create("Trying to retrieve not existing method annotation {$className} for {$this}");
    }

    /**
     * @return object[]
     */
    public function getMethodAnnotationsOf(ObjectType|string $className): array
    {
        $methodAnnotations = [];
        $classNameType = ObjectType::from($className);
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if ($classNameType->accepts($methodAnnotation)) {
                $methodAnnotations[] = $methodAnnotation;
            }
        }

        return $methodAnnotations;
    }

    public function isStaticallyCalled(): bool
    {
        return $this->isStaticallyCalled;
    }

    /**
     * @return bool
     */
    public function canReturnValue(): bool
    {
        return ! $this->getReturnType()->isVoid();
    }

    public function getReturnType(): ?Type
    {
        return $this->returnType;
    }

    /**
     * @return bool
     */
    public function hasReturnTypeVoid(): bool
    {
        return $this->getReturnType()->isVoid();
    }

    /**
     * @return bool
     */
    public function hasReturnValueBoolean(): bool
    {
        return $this->getReturnType()->isBoolean();
    }

    /**
     * @return bool
     */
    public function doesItReturnIterable(): bool
    {
        return $this->getReturnType()->isIterable();
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function getFirstParameterName(): string
    {
        return $this->getFirstParameter()->getName();
    }

    /**
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function getFirstParameter(): InterfaceParameter
    {
        if ($this->parameterAmount() < 1) {
            throw InvalidArgumentException::create("Expecting {$this} to have at least one parameter, but got none");
        }

        return $this->getInterfaceParameters()[0];
    }

    /**
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function getSecondParameter(): InterfaceParameter
    {
        if ($this->parameterAmount() < 2) {
            throw InvalidArgumentException::create("Expecting {$this} to have at least two parameters");
        }

        return $this->getInterfaceParameters()[1];
    }

    public function getThirdParameter(): InterfaceParameter
    {
        if ($this->parameterAmount() < 3) {
            throw InvalidArgumentException::create("Expecting {$this} to have at least three parameter");
        }

        return $this->getInterfaceParameters()[2];
    }

    /**
     * @return array|InterfaceParameter[]
     */
    public function getInterfaceParameters(): iterable
    {
        return $this->parameters;
    }

    /**
     * @return string[]
     */
    public function getInterfaceParametersNames(): iterable
    {
        return array_map(fn ($param) => $param->getName(), $this->parameters);
    }

    /**
     * @return int
     */
    public function getInterfaceParameterAmount(): int
    {
        return count($this->parameters);
    }

    /**
     * @param int $index
     *
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function getParameterAtIndex(int $index): InterfaceParameter
    {
        if (! array_key_exists($index, $this->getInterfaceParameters())) {
            throw InvalidArgumentException::create("There is no parameter at index {$index} for {$this}");
        }

        return $this->parameters[$index];
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function hasFirstParameterMessageTypeHint(): bool
    {
        return $this->parameterAmount() > 0 && $this->getFirstParameter()->isMessage();
    }

    /**
     * @return bool
     */
    public function doesItReturnFuture(): bool
    {
        return $this->getReturnType()->isClassOfType(Future::class);
    }

    /**
     * @return bool
     */
    public function isReturnTypeUnknown(): bool
    {
        return $this->getReturnType()->isAnything();
    }

    /**
     * @return bool
     */
    public function doesItReturnMessage(): bool
    {
        return $this->getReturnType()->isClassOfType(Message::class);
    }

    public function getInterfaceType(): ObjectType
    {
        return Type::object($this->interfaceName);
    }

    public function getMethodName(): ?string
    {
        return $this->methodName;
    }

    /**
     * @param string $methodName
     *
     * @return bool
     */
    public function hasMethodName(string $methodName): bool
    {
        return $this->getMethodName() === $methodName;
    }

    /**
     * @return bool
     */
    public function canItReturnNull(): bool
    {
        return is_null($this->returnType) || $this->doesReturnTypeAllowNulls;
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function getFirstParameterTypeHint(): string
    {
        if ($this->parameterAmount() < 1) {
            throw InvalidArgumentException::create("Trying to get first parameter, but has none for {$this}");
        }

        return $this->getFirstParameter()->getTypeHint();
    }

    /**
     * @param string $parameterName
     *
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function getParameterWithName(string $parameterName): InterfaceParameter
    {
        foreach ($this->getInterfaceParameters() as $parameter) {
            if ($parameter->getName() == $parameterName) {
                return $parameter;
            }
        }

        throw InvalidArgumentException::create($this . " doesn't have parameter with name {$parameterName}");
    }

    /**
     * @return bool
     */
    public function hasMoreThanOneParameter(): bool
    {
        return $this->parameterAmount() > 1;
    }

    /**
     * @return bool
     */
    public function hasNoParameters(): bool
    {
        return $this->parameterAmount() == 0;
    }

    /**
     * @return bool
     */
    public function hasSingleParameter(): bool
    {
        return $this->parameterAmount() == 1;
    }

    public function hasFirstParameter(): bool
    {
        return $this->parameterAmount() >= 1;
    }

    public function hasSecondParameter(): bool
    {
        return $this->parameterAmount() >= 2;
    }

    public function hasThirdParameter(): bool
    {
        return $this->parameterAmount() >= 3;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->interfaceName}::{$this->methodName}";
    }

    public function toString(): string
    {
        return (string)$this;
    }

    /**
     * @return int
     */
    private function parameterAmount(): int
    {
        return count($this->getInterfaceParameters());
    }

    public function isReturningAggregate(InterfaceToCallRegistry $interfaceToCallRegistry): bool
    {
        if ($this->getReturnType()?->isClassNotInterface()) {
            $returnTypeInterface = $interfaceToCallRegistry->getClassDefinitionFor(Type::object($this->getReturnType()->toString()));

            return $returnTypeInterface->hasClassAnnotation(Type::attribute(Aggregate::class));
        }

        return false;
    }

    public function isFactoryMethod(): bool
    {
        return $this->isStaticallyCalled || $this->methodName === '__construct';
    }
}
