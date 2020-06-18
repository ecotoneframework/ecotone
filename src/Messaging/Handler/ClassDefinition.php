<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class ClassDefinition
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ClassDefinition
{
    private Type $classDescriptor;
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

    /**
     * ClassDefinition constructor.
     * @param Type $classDescriptor
     * @param ClassPropertyDefinition[] $properties
     * @param object[] $annotations
     * @param string[] $publicMethodNames
     * @param bool $isAnnotation
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct(Type $classDescriptor, iterable $properties, iterable $annotations, array $publicMethodNames, bool $isAnnotation)
    {
        Assert::isTrue($classDescriptor->isClassOrInterface(), "Cannot create class definition from non class " . $classDescriptor->toString());

        $this->classDescriptor = $classDescriptor;
        $this->properties = $properties;
        $this->classAnnotations = $annotations;
        $this->publicMethodNames = $publicMethodNames;
        $this->isAnnotation = $isAnnotation;
    }

    /**
     * @param Type $classType
     * @return ClassDefinition
     * @throws TypeDefinitionException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createFor(Type $classType) : self
    {
        $annotationParser = InMemoryAnnotationRegistrationService::createFrom([$classType->toString()]);
        $typeResolver = TypeResolver::create();

        $reflectionClass = new \ReflectionClass($classType->toString());

        return new self(
            $classType,
            $typeResolver->getClassProperties($classType->toString()),
            $annotationParser->getAnnotationsForClass($classType->toString()),
            array_map(function(\ReflectionMethod $method){return $method->getName();},$reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC)),
            (bool)preg_match("/@Annotation$/m", $reflectionClass->getDocComment() ? $reflectionClass->getDocComment() : "")
        );
    }

    /**
     * @param Type $classType
     * @param AnnotationParser $annotationParser
     * @return ClassDefinition
     * @throws TypeDefinitionException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createUsingAnnotationParser(Type $classType, AnnotationParser $annotationParser)
    {
        $typeResolver = TypeResolver::createWithAnnotationParser($annotationParser);

        $reflectionClass = new \ReflectionClass($classType->toString());

        return new self(
            $classType,
            $typeResolver->getClassProperties($classType->toString()),
            $annotationParser->getAnnotationsForClass($classType->toString()),
            array_map(function(\ReflectionMethod $method){return $method->getName();},$reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC)),
            (bool)preg_match("/@Annotation$/m", $reflectionClass->getDocComment() ? $reflectionClass->getDocComment() : "")
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
}