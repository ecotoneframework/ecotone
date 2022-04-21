<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Future;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Class InterfaceToCall
 * @package Ecotone\Messaging\Handler\Gateway\Gateway
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCall
{
    private ?string $interfaceName;
    private ?Type $interfaceType;
    private ?string $methodName;
    /**
     * @var InterfaceParameter[]
     */
    private ?iterable $parameters;
    private ?Type $returnType;
    private ?bool $doesReturnTypeAllowNulls;
    private ?bool $isStaticallyCalled;
    /**
     * @var object[]
     */
    private array $methodAnnotations;
    /**
     * @var object[]
     */
    private array $classAnnotations;

    /**
     * @param object[]        $methodAnnotations
     */
    private function __construct(string $interfaceName, string $methodName, array $classAnnotations, array $methodAnnotations, AnnotationResolver $annotationResolver)
    {
        $this->methodAnnotations = $methodAnnotations;
        $this->classAnnotations = $classAnnotations;
        $this->initialize($interfaceName, $methodName, $annotationResolver);
    }

    public static function create(string|object $interfaceOrObjectName, string $methodName): self
    {
        $interface = $interfaceOrObjectName;
        if (is_object($interfaceOrObjectName)) {
            $interface = get_class($interfaceOrObjectName);
        }

        $annotationParser = InMemoryAnnotationFinder::createFrom([$interface]);

        $methodAnnotations = $annotationParser->getAnnotationsForMethod($interface, $methodName);
        $classAnnotations = $annotationParser->getAnnotationsForClass($interface);

        return new self($interface, $methodName, $classAnnotations, $methodAnnotations, $annotationParser);
    }

    public static function createWithAnnotationFinder(string|object $interfaceOrObjectName, string $methodName, AnnotationResolver $annotationParser): self
    {
        $interface = $interfaceOrObjectName;
        if (is_object($interfaceOrObjectName)) {
            $interface = get_class($interfaceOrObjectName);
        }

        $methodAnnotations = $annotationParser->getAnnotationsForMethod($interface, $methodName);
        $classAnnotations = $annotationParser->getAnnotationsForClass($interface);

        return new self($interface, $methodName, $classAnnotations, $methodAnnotations, $annotationParser);
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
     * @param Type $className
     *
     * @return bool
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function hasMethodAnnotation(Type $className): bool
    {
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if (TypeDescriptor::createFromVariable($methodAnnotation)->equals($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Type $className
     *
     * @return bool
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function hasClassAnnotation(Type $className): bool
    {
        foreach ($this->getClassAnnotations() as $classAnnotation) {
            if (TypeDescriptor::createFromVariable($classAnnotation)->equals($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Type $className
     *
     * @throws MessagingException
     */
    public function getClassAnnotation(Type $className): object
    {
        foreach ($this->getClassAnnotations() as $classAnnotation) {
            if (TypeDescriptor::createFromVariable($classAnnotation)->equals($className)) {
                return $classAnnotation;
            }
        }

        throw InvalidArgumentException::create("Trying to retrieve not existing class annotation {$className} for {$this}");
    }

    /**
     * @param Type $className
     *
     * @throws MessagingException
     */
    public function getMethodAnnotation(Type $className): object
    {
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if (TypeDescriptor::createFromVariable($methodAnnotation)->equals($className)) {
                return $methodAnnotation;
            }
        }

        throw InvalidArgumentException::create("Trying to retrieve not existing method annotation {$className} for {$this}");
    }

    public function isStaticallyCalled(): ?bool
    {
        return $this->isStaticallyCalled;
    }

    /**
     * @return bool
     */
    public function canReturnValue(): bool
    {
        return !$this->getReturnType()->isVoid();
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
    public function getInterfaceParameters(): ?iterable
    {
        return $this->parameters;
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
        if (!array_key_exists($index, $this->getInterfaceParameters())) {
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

    public function getInterfaceType(): ?Type
    {
        return $this->interfaceType;
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

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->interfaceName}::{$this->methodName}";
    }

    /**
     * @return int
     */
    private function parameterAmount(): int
    {
        return count($this->getInterfaceParameters());
    }

    private function initialize(string $interfaceName, string $methodName, AnnotationResolver $annotationResolver): void
    {
        try {
            $typeResolver        = TypeResolver::create();
            $this->interfaceType = TypeDescriptor::create($interfaceName);
            $this->interfaceName = $this->interfaceType->toString();
            $this->methodName    = $methodName;
            try {
                $reflectionClass = new \ReflectionClass($interfaceName);
                $reflectionMethod = $reflectionClass->getMethod($methodName);
            }catch (\ReflectionException) {
                throw InvalidArgumentException::create("Interface {$interfaceName} has no method named {$methodName}");
            }

            $this->parameters = $typeResolver->getMethodParameters($reflectionClass, $methodName, $annotationResolver);
            $this->returnType = $typeResolver->getReturnType($reflectionClass, $methodName);

            $this->doesReturnTypeAllowNulls = $reflectionMethod->getReturnType() ? $reflectionMethod->getReturnType()->allowsNull() : true;
            $this->isStaticallyCalled       = $reflectionMethod->isStatic();
        } catch (TypeDefinitionException $definitionException) {
            throw InvalidArgumentException::create("Interface {$this} has problem with type declaration. {$definitionException->getMessage()}");
        }
    }
}