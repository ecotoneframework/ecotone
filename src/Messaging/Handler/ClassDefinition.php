<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;

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
    private $annotations;

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
        $this->annotations = $annotations;
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
     * @return ClassPropertyDefinition[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param string $name
     * @return ClassPropertyDefinition
     */
    public function getProperty(string $name) : ClassPropertyDefinition
    {
        foreach ($this->properties as $property) {
            if ($property->hasName($name)) {
                return $property;
            }
        }
    }

    /**
     * @return object[]
     */
    public function getAnnotations(): array
    {
        return $this->annotations;
    }
}