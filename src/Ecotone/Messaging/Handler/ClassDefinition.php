<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\BeforeMultiplyCalculation;
use Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate\AddExecutorId\AddExecutorId;

/**
 * Class ClassDefinition
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ClassDefinition
{
    private TypeDescriptor $classDescriptor;
    /**
     * @var ClassPropertyDefinition[]
     */
    private array $properties;
    /**
     * @var object[]
     */
    private array $classAnnotations;
    /**
     * @var string[]
     */
    private array $publicMethodNames;
    private bool $isAnnotation;

    private function __construct(TypeDescriptor $classDescriptor, array $properties, array $annotations, array $publicMethodNames, bool $isAnnotation)
    {
        Assert::isTrue($classDescriptor->isClassOrInterface(), "Cannot create class definition from non class " . $classDescriptor->toString());

        $this->classDescriptor = $classDescriptor;
        $this->properties = $properties;
        $this->classAnnotations = $annotations;
        $this->publicMethodNames = $publicMethodNames;
        $this->isAnnotation = $isAnnotation;
    }

    public static function createFor(TypeDescriptor $classType) : self
    {
        $annotationParser = InMemoryAnnotationFinder::createFrom([$classType->toString()]);
        $typeResolver = TypeResolver::create();

        $reflectionClass = new \ReflectionClass($classType->toString());

        return new self(
            $classType,
            $typeResolver->getClassProperties($classType->toString()),
            $annotationParser->getAnnotationsForClass($classType->toString()),
            array_map(function(\ReflectionMethod $method){return $method->getName();},$reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC)),
            (bool)!empty($reflectionClass->getAttributes(\Attribute::class))
        );
    }

    public static function createUsingAnnotationParser(TypeDescriptor $classType, AnnotationResolver $annotationParser): \Ecotone\Messaging\Handler\ClassDefinition
    {
        $typeResolver = TypeResolver::createWithAnnotationParser($annotationParser);

        $reflectionClass = new \ReflectionClass($classType->toString());

        return new self(
            $classType,
            $typeResolver->getClassProperties($classType->toString()),
            $annotationParser->getAnnotationsForClass($classType->toString()),
            array_map(function(\ReflectionMethod $method){return $method->getName();},$reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC)),
            (bool)!empty($reflectionClass->getAttributes(\Attribute::class))
        );
    }

    /**
     * @return ClassPropertyDefinition[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return string[]
     */
    public function getPublicMethodNames(): array
    {
        return $this->publicMethodNames;
    }

    /**
     * @param string $name
     *
     * @return ClassPropertyDefinition
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function getProperty(string $name) : ClassPropertyDefinition
    {
        foreach ($this->properties as $property) {
            if ($property->hasName($name)) {
                return $property;
            }
        }

        throw InvalidArgumentException::create("There is no property with name {$name} in {$this->classDescriptor->toString()}");
    }

    public function getClassType() : TypeDescriptor
    {
        return $this->classDescriptor;
    }

    public function hasProperty(string $name) : bool
    {
        foreach ($this->properties as $property) {
            if ($property->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ClassPropertyDefinition[]
     */
    public function getPropertiesWithAnnotation(Type $annotationClass) : array
    {
        $propertiesWithAnnotation = [];
        foreach ($this->properties as $property) {
            if ($property->hasAnnotation($annotationClass)) {
                $propertiesWithAnnotation[] = $property;
            }
        }

        return $propertiesWithAnnotation;
    }

    /**
     * @return object[]
     */
    public function getClassAnnotations(): array
    {
        return $this->classAnnotations;
    }

    public function getSingleClassAnnotation(TypeDescriptor $annotationType) : object
    {
        $foundAnnotations = [];
        foreach ($this->classAnnotations as $classAnnotation) {
            if (TypeDescriptor::createFromVariable($classAnnotation)->equals($annotationType)) {
                $foundAnnotations[] = $classAnnotation;
            }
        }

        if (count($foundAnnotations) < 1) {
            throw InvalidArgumentException::create("Attribute {$annotationType} was not found for {$this}");
        }
        if (count($foundAnnotations) > 1) {
            throw InvalidArgumentException::create("Looking for single attribute {$annotationType}, however found more than one");
        }

        return $foundAnnotations[0];
    }

    public function isAnnotation() : bool
    {
        return $this->isAnnotation;
    }

    public function hasClassAnnotation(Type $type) : bool
    {
        foreach ($this->getClassAnnotations() as $classAnnotation) {
            if (TypeDescriptor::createFromVariable($classAnnotation)->equals($type)) {
                return true;
            }
        }

        return false;
    }

    public function __toString()
    {
        return $this->getClassType()->toString();
    }
}