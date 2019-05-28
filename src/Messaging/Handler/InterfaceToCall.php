<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use Doctrine\Common\Annotations\AnnotationException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Future;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class InterfaceToCall
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCall
{
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var TypeDescriptor
     */
    private $interfaceType;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|InterfaceParameter[]
     */
    private $parameters;
    /**
     * @var TypeDescriptor
     */
    private $returnType;
    /**
     * @var bool
     */
    private $doesReturnTypeAllowNulls;
    /**
     * @var bool
     */
    private $isStaticallyCalled;
    /**
     * @var iterable|object[]
     */
    private $methodAnnotations;
    /**
     * @var ClassDefinition
     */
    private $classDefinition;

    /**
     * InterfaceToCall constructor.
     * @param string $interfaceName
     * @param string $methodName
     * @param ClassDefinition $classDefinition
     * @param object[] $methodAnnotations
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    private function __construct(string $interfaceName, string $methodName, ClassDefinition $classDefinition, iterable $methodAnnotations = [])
    {
        $this->initialize($interfaceName, $methodName);
        $this->methodAnnotations = $methodAnnotations;
        $this->classDefinition = $classDefinition;
    }

    /**
     * @return string
     */
    public function getInterfaceName(): string
    {
        return $this->interfaceName;
    }

    /**
     * @param string|object $interfaceOrObjectName
     * @param string $methodName
     * @return InterfaceToCall
     * @throws InvalidArgumentException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public static function create($interfaceOrObjectName, string $methodName): self
    {
        $interface = $interfaceOrObjectName;
        if (is_object($interfaceOrObjectName)) {
            $interface = get_class($interfaceOrObjectName);
        }

        $annotationParser = InMemoryAnnotationRegistrationService::createFrom([$interface]);

        return new self($interface, $methodName, ClassDefinition::createUsingAnnotationParser(TypeDescriptor::create($interface), $annotationParser), $annotationParser->getAnnotationsForMethod($interface, $methodName));
    }

    /**
     * @return object[]
     */
    public function getMethodAnnotations(): iterable
    {
        return $this->methodAnnotations;
    }

    /**
     * @return object[]
     */
    public function getClassAnnotations(): iterable
    {
        return $this->getClassDefinition()->getClassAnnotations();
    }

    /**
     * @return ClassDefinition
     */
    public function getClassDefinition() : ClassDefinition
    {
        return $this->classDefinition;
    }

    /**
     * @param TypeDescriptor $className
     * @return bool
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function hasMethodAnnotation(TypeDescriptor $className): bool
    {
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if (TypeDescriptor::createFromVariable($methodAnnotation)->equals($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TypeDescriptor $className
     * @return bool
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function hasClassAnnotation(TypeDescriptor $className): bool
    {
        foreach ($this->getClassAnnotations() as $classAnnotation) {
            if (TypeDescriptor::createFromVariable($classAnnotation)->equals($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TypeDescriptor $className
     * @return object
     * @throws MessagingException
     */
    public function getClassAnnotation(TypeDescriptor $className)
    {
        foreach ($this->getClassAnnotations() as $classAnnotation) {
            if (TypeDescriptor::createFromVariable($classAnnotation)->equals($className)) {
                return $classAnnotation;
            }
        }

        throw InvalidArgumentException::create("Trying to retrieve not existing class annotation {$className} for {$this}");
    }

    /**
     * @param TypeDescriptor $className
     * @return object
     * @throws MessagingException
     */
    public function getMethodAnnotation(TypeDescriptor $className)
    {
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if (TypeDescriptor::createFromVariable($methodAnnotation)->equals($className)) {
                return $methodAnnotation;
            }
        }

        throw InvalidArgumentException::create("Trying to retrieve not existing method annotation {$className} for {$this}");
    }

    /**
     * @return bool
     */
    public function isStaticallyCalled(): bool
    {
        return $this->isStaticallyCalled;
    }

    /**
     * @return bool
     */
    public function hasReturnValue(): bool
    {
        return !$this->getReturnType()->isVoid();
    }

    /**
     * @return TypeDescriptor
     */
    public function getReturnType(): TypeDescriptor
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
            throw InvalidArgumentException::create("Expecting {$this} to have at least one parameter, but got none");
        }

        return $this->getInterfaceParameters()[1];
    }

    /**
     * @return int
     */
    private function parameterAmount(): int
    {
        return count($this->getInterfaceParameters());
    }

    /**
     * @return array|InterfaceParameter[]
     */
    public function getInterfaceParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param int $index
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
        return $this->getReturnType()->isUnknown();
    }

    /**
     * @return bool
     */
    public function doesItReturnMessage(): bool
    {
        return $this->getReturnType()->isClassOfType(Message::class);
    }

    /**
     * @return TypeDescriptor
     */
    public function getInterfaceType() : TypeDescriptor
    {
        return $this->interfaceType;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function hasMethodName(string $methodName) : bool
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
    public function hasSingleArgument(): bool
    {
        return $this->parameterAmount() == 1;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->interfaceName}::{$this->methodName}";
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    private function initialize(string $interfaceName, string $methodName): void
    {
        try {
            $typeResolver = TypeResolver::create();
            $this->interfaceType = TypeDescriptor::create($interfaceName);
            $this->interfaceName = $this->interfaceType->toString();
            $this->methodName = $methodName;
            $reflectionClass = new ReflectionClass($interfaceName);
            if (!$reflectionClass->hasMethod($methodName)) {
                throw InvalidArgumentException::create("Interface {$interfaceName} has no method named {$methodName}");
            }
            $reflectionMethod = new ReflectionMethod($interfaceName, $methodName);

            $this->parameters = $typeResolver->getMethodParameters($interfaceName, $methodName);
            $this->returnType = $typeResolver->getReturnType($interfaceName, $methodName);

            $this->doesReturnTypeAllowNulls = $reflectionMethod->getReturnType() ? $reflectionMethod->getReturnType()->allowsNull() : true;
            $this->isStaticallyCalled = $reflectionMethod->isStatic();
        } catch (TypeDefinitionException $definitionException) {
            throw InvalidArgumentException::create("Interface {$this} has problem with type declaration. {$definitionException->getMessage()}");
        }
    }
}