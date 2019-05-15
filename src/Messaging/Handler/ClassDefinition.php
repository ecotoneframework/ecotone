<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class ClassDefinition
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ClassDefinition
{
    /**
     * @var TypeDescriptor
     */
    private $classDescriptor;
    /**
     * @var ClassPropertyDefinition[]
     */
    private $properties;
    /**
     * @var object[]
     */
    private $classAnnotations;

    /**
     * ClassDefinition constructor.
     * @param TypeDescriptor $classDescriptor
     * @param ClassPropertyDefinition[] $properties
     * @param object[] $annotations
     */
    private function __construct(TypeDescriptor $classDescriptor, iterable $properties, iterable $annotations)
    {
        $this->classDescriptor = $classDescriptor;
        $this->properties = $properties;
        $this->classAnnotations = $annotations;
    }

    /**
     * @param TypeDescriptor $classType
     * @return ClassDefinition
     * @throws TypeDefinitionException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createFor(TypeDescriptor $classType) : self
    {
        $annotationParser = InMemoryAnnotationRegistrationService::createFrom([$classType->toString()]);
        $typeResolver = TypeResolver::create();

        return new self(
            $classType,
            $typeResolver->getClassProperties($classType->toString()),
            $annotationParser->getAnnotationsForClass($classType->toString())
        );
    }

    /**
     * @param TypeDescriptor $classType
     * @param AnnotationParser $annotationParser
     * @return ClassDefinition
     * @throws TypeDefinitionException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createUsingAnnotationParser(TypeDescriptor $classType, AnnotationParser $annotationParser)
    {
        $typeResolver = TypeResolver::createWithAnnotationParser($annotationParser);

        return new self(
            $classType,
            $typeResolver->getClassProperties($classType->toString()),
            $annotationParser->getAnnotationsForClass($classType->toString())
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
     * @param string $name
     *
     * @return ClassPropertyDefinition
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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

    /**
     * @param TypeDescriptor $annotationClass
     * @return array
     * @throws TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getPropertiesWithAnnotation(TypeDescriptor $annotationClass)
    {
        $propertiesWithAnnotation = [];
        foreach ($this->properties as $property) {
            foreach ($property->getAnnotations() as $annotation) {
                if (TypeDescriptor::createFromVariable($annotation)->equals($annotationClass)) {
                    $propertiesWithAnnotation[] = $property;
                    break;
                }
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
}