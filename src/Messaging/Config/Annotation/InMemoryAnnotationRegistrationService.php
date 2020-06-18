<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Ecotone\Messaging\Handler\AnnotationParser;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\TypeResolver;

/**
 * Class InMemoryAnnotationRegistrationService
 * @package Ecotone\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryAnnotationRegistrationService implements AnnotationRegistrationService, AnnotationParser
{
    private const CLASS_ANNOTATIONS = "classAnnotations";
    private const CLASS_PROPERTIES = "classProperties";

    /**
     * @var array
     */
    private $annotationsForClass;

    private function __construct()
    {
        $this->annotationsForClass[self::CLASS_ANNOTATIONS] = [];
    }

    /**
     * @return InMemoryAnnotationRegistrationService
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @param array $classesWithAnnotationToRegister
     * @return InMemoryAnnotationRegistrationService
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public static function createFrom(array $classesWithAnnotationToRegister) : self
    {
        $annotationRegistrationService = self::createEmpty();

        foreach ($classesWithAnnotationToRegister as $classToRegister) {
            $annotationRegistrationService->registerClassWithAnnotations($classToRegister);
        }

        return $annotationRegistrationService;
    }

    /**
     * @param string $className
     * @return InMemoryAnnotationRegistrationService
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function registerClassWithAnnotations(string $className) : self
    {
        $annotationReader = new AnnotationReader();

        $reflectionClass = new \ReflectionClass($className);
        foreach (get_class_methods($className) as $method) {
            $methodOwnerClass = TypeResolver::getMethodOwnerClass($reflectionClass, $method)->getName();
            $methodReflection = new \ReflectionMethod($methodOwnerClass, $method);
            foreach ($annotationReader->getMethodAnnotations($methodReflection) as $methodAnnotation) {
                $this->addAnnotationToClassMethod($className, $method, $methodAnnotation);
            }
        }

        foreach ($annotationReader->getClassAnnotations($reflectionClass) as $classAnnotation) {
            $this->addAnnotationToClass($className, $classAnnotation);
        }
        foreach ($reflectionClass->getProperties() as $property) {
            foreach ($annotationReader->getPropertyAnnotations($property) as $annotation) {
                if (!$this->hasRegisteredAnnotationForProperty($reflectionClass->getName(), $property->getName(), $annotation)) {
                    $this->addAnnotationToProperty($reflectionClass->getName(), $property->getName(), $annotation);
                }
            }
        }
        $parentClass = $reflectionClass;
        do {
            foreach ($parentClass->getProperties() as $property) {
                foreach ($annotationReader->getPropertyAnnotations($property) as $annotation) {
                    if (!$this->hasRegisteredAnnotationForProperty($reflectionClass->getName(), $property->getName(), $annotation)) {
                        $this->addAnnotationToProperty($reflectionClass->getName(), $property->getName(), $annotation);
                    }
                    if (!$this->hasRegisteredAnnotationForProperty($parentClass->getName(), $property->getName(), $annotation)) {
                        $this->addAnnotationToProperty($parentClass->getName(), $property->getName(), $annotation);
                    }
                }
            }
        }while($parentClass = $parentClass->getParentClass());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForMethod(string $className, string $methodName): iterable
    {
        if (!isset($this->annotationsForClass[$className][$methodName])) {
            return [];
        }

        return array_values($this->annotationsForClass[$className][$methodName]);
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForClass(string $classNameToFind): iterable
    {
        if (!isset($this->annotationsForClass[self::CLASS_ANNOTATIONS][$classNameToFind])) {
            return [];
        }

        return array_values($this->annotationsForClass[self::CLASS_ANNOTATIONS][$classNameToFind]);
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationsForProperty(string $className, string $propertyName): iterable
    {
        if (!isset($this->annotationsForClass[self::CLASS_PROPERTIES][$className][$propertyName])) {
            return [];
        }

        return $this->annotationsForClass[self::CLASS_PROPERTIES][$className][$propertyName];
    }

    /**
     * @inheritDoc
     */
    public function findRegistrationsFor(string $classAnnotationName, string $methodAnnotationClassName): array
    {
        $classes = $this->getAllClassesWithAnnotation($classAnnotationName);

        $registrations = [];
        foreach ($classes as $class) {
            if (!isset($this->annotationsForClass[$class])) {
                continue;
            }

            foreach ($this->annotationsForClass[$class] as $methodName => $methodAnnotations) {
                foreach ($methodAnnotations as $methodAnnotation) {
                    if (get_class($methodAnnotation) == $methodAnnotationClassName  || $methodAnnotation instanceof $methodAnnotationClassName) {
                        $registrations[] = AnnotationRegistration::create(
                            $this->annotationsForClass[self::CLASS_ANNOTATIONS][$class][$classAnnotationName],
                            $methodAnnotation,
                            $class,
                            $methodName,
                            $this->getAnnotationsForClass($class),
                            $methodAnnotations
                        );
                    }
                }
            }
        }

        return $registrations;
    }

    /**
     * @inheritDoc
     */
    public function getAllClassesWithAnnotation(string $annotationClassName): array
    {
        $classes = [];

        foreach ($this->annotationsForClass[self::CLASS_ANNOTATIONS] as $className => $annotations) {
            foreach ($annotations as $annotation) {
                if (get_class($annotation) == $annotationClassName) {
                    $classes[] = $className;
                }
            }
        }

        return $classes;
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationForClass(string $className, string $annotationClassName)
    {
        return isset($this->annotationsForClass[self::CLASS_ANNOTATIONS][$className][$annotationClassName])
            ? $this->annotationsForClass[self::CLASS_ANNOTATIONS][$className][$annotationClassName]
            : null;
    }

    /**
     * @param string $className
     * @param $classAnnotationObject
     * @return InMemoryAnnotationRegistrationService
     */
    public function addAnnotationToClass(string $className, $classAnnotationObject): self
    {
        $this->annotationsForClass[self::CLASS_ANNOTATIONS][$className][get_class($classAnnotationObject)] = $classAnnotationObject;

        return $this;
    }

    /**
     * @param string $className
     * @param string $property
     * @param $propertyAnnotationObject
     * @return InMemoryAnnotationRegistrationService
     */
    public function addAnnotationToProperty(string $className, string $property, $propertyAnnotationObject) : self
    {
        $this->annotationsForClass[self::CLASS_PROPERTIES][$className][$property][] = $propertyAnnotationObject;

        return $this;
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param $methodAnnotationObject
     * @return InMemoryAnnotationRegistrationService
     */
    public function addAnnotationToClassMethod(string $className, string $methodName, $methodAnnotationObject): self
    {
        $this->annotationsForClass[$className][$methodName][get_class($methodAnnotationObject)] = $methodAnnotationObject;

        return $this;
    }

    private function hasRegisteredAnnotationForProperty(string $className, string $propertyName, $annotation): bool
    {
        foreach ($this->getAnnotationsForProperty($className, $propertyName) as $registeredAnnotation) {
            if ($registeredAnnotation == $annotation) {
                return true;
            }
        }

        return false;
    }
}