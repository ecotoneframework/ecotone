<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation;

use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;

class AnnotationRegistration
{
    private object $annotationForClass;
    /**
     * Annotation to register
     */
    private object $annotationForMethod;
    /**
     * Message endpoint class containing the annotation
     */
    private string $className;
    /**
     * Reference name to object
     */
    private string $referenceName;

    private string $methodName;
    /**
     * @var object[]
     */
    private array $methodAnnotations;
    /**
     * @var object[]
     */
    private array $classAnnotations;

    private function __construct(object $annotationForClass, object $annotationForMethod, string $className, string $methodName, array $classAnnotations, array $methodAnnotations)
    {
        Assert::isObject($annotationForClass, "Annotation for class should be object");
        Assert::isObject($annotationForMethod, "Found annotation should be object");

        $this->annotationForClass = $annotationForClass;
        $this->annotationForMethod = $annotationForMethod;
        $this->className = $className;
        $this->methodName = $methodName;
        $this->methodAnnotations = $methodAnnotations;

        $this->initialize($annotationForClass, $className);
        $this->classAnnotations = $classAnnotations;
    }

    /**
     * @param object[] $classAnnotations
     * @param object[] $methodAnnotations
     */
    public static function create(object $annotationForClass, object $annotationForMethod, string $className, string $methodName, array $classAnnotations, array $methodAnnotations) : self
    {
        return new self($annotationForClass, $annotationForMethod, $className, $methodName, $classAnnotations, $methodAnnotations);
    }

    /**
     * @return object
     */
    public function getAnnotationForClass()
    {
        return $this->annotationForClass;
    }

    /**
     * @return object
     */
    public function getAnnotationForMethod()
    {
        return $this->annotationForMethod;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getMethodAnnotations(): array
    {
        return $this->methodAnnotations;
    }

    public function hasMethodAnnotation(Type $typeDescriptor) : bool
    {
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if (TypeDescriptor::createFromVariable($methodAnnotation)->equals($typeDescriptor)) {
                return true;
            }
        }

        return false;
    }

    public function hasClassAnnotation(Type $typeDescriptor) : bool
    {
        foreach ($this->classAnnotations as $classAnnotation) {
            if (TypeDescriptor::createFromVariable($classAnnotation)->equals($typeDescriptor)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object $annotationForClass
     * @param string $classNameWithAnnotation
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function initialize($annotationForClass, string $classNameWithAnnotation) : void
    {
        Assert::isObject($annotationForClass, "Class for annotation must be object");

        $this->referenceName = (property_exists($annotationForClass, 'referenceName') && $annotationForClass->referenceName) ? $annotationForClass->referenceName : $classNameWithAnnotation;
    }

    public function __toString()
    {
        return $this->className . "::" . $this->methodName . "::" . get_class($this->annotationForMethod);
    }
}