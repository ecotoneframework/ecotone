<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use SimplyCodedSoftware\Messaging\Handler\AnnotationParser;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Class InMemoryAnnotationRegistrationService
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
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
     */
    public function registerClassWithAnnotations(string $className) : self
    {
        $annotationReader = new AnnotationReader();

        $reflectionClass = new \ReflectionClass($className);
        foreach ($annotationReader->getClassAnnotations($reflectionClass) as $classAnnotation) {
            $this->addAnnotationToClass($className, $classAnnotation);
        }
        foreach ($reflectionClass->getProperties() as $property) {
            foreach ($annotationReader->getPropertyAnnotations($property) as $annotation) {
                $this->addAnnotationToProperty($className, $property->getName(), $annotation);
            }
        }

        foreach (get_class_methods($className) as $method) {
            $methodReflection = new \ReflectionMethod($className, $method);
            foreach ($annotationReader->getMethodAnnotations($methodReflection) as $methodAnnotation) {
                $this->addAnnotationToClassMethod($className, $method, $methodAnnotation);
            }
        }

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
                            $methodName
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
                if (!is_object($annotation)) {
                    var_dump($annotation);die();
                }
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

    /**
     * @param string $className
     * @param string $methodName
     * @param string $annotationClassName
     * @return InMemoryAnnotationRegistrationService
     */
    public function resetClassMethodAnnotation(string $className, string $methodName, string $annotationClassName) : self
    {
        unset($this->annotationsForClass[$className][$methodName][$annotationClassName]);

        return $this;
    }
}