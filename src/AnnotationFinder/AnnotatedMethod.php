<?php

namespace Ecotone\AnnotationFinder;

class AnnotatedMethod implements AnnotatedFinding
{
    private string $className;
    private string $methodName;
    private object $annotationForMethod;
    /**
     * @var object[]
     */
    private array $methodAnnotations;
    /**
     * @var object[]
     */
    private array $classAnnotations;

    private function __construct(object $annotationForMethod, string $className, string $methodName, array $classAnnotations, array $methodAnnotations)
    {
        $this->annotationForMethod = $annotationForMethod;
        $this->className = $className;
        $this->methodName = $methodName;
        $this->methodAnnotations = $methodAnnotations;
        $this->classAnnotations = $classAnnotations;
    }

    /**
     * @param object[] $classAnnotations
     * @param object[] $methodAnnotations
     */
    public static function create(object $annotationForMethod, string $className, string $methodName, array $classAnnotations, array $methodAnnotations) : self
    {
        return new self($annotationForMethod, $className, $methodName, $classAnnotations, $methodAnnotations);
    }

    public function getAnnotationForMethod() : object
    {
        return $this->annotationForMethod;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return object[]
     */
    public function getMethodAnnotations(): array
    {
        return $this->methodAnnotations;
    }

    public function hasMethodAnnotation(string $type) : bool
    {
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if ($methodAnnotation instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return object[]
     */
    public function getClassAnnotations() : array
    {
        return $this->classAnnotations;
    }

    /**
     * @return object[]
     * @throws \InvalidArgumentException if not found any
     */
    public function getClassAnnotationsWithType(string $type) : array
    {
        $annotations = [];
        foreach ($this->classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof $type) {
                $annotations[] = $classAnnotation;
            }
        }

        if (empty($annotations)) {
            throw new \InvalidArgumentException("Trying to retrieve class annotation {$type}, but there is no any for {$this}");
        }

        return $annotations;
    }

    /**
     * @return object[]
     * @throws \InvalidArgumentException if not found any
     */
    public function getMethodAnnotationsWithType(string $type) : array
    {
        $annotations = [];
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if ($methodAnnotation instanceof $type) {
                $annotations[] = $methodAnnotation;
            }
        }

        if (empty($annotations)) {
            throw new \InvalidArgumentException("Trying to retrieve class annotation {$type}, but there is no any for {$this}");
        }

        return $annotations;
    }

    public function hasClassAnnotation(string $type) : bool
    {
        foreach ($this->classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof $type) {
                return true;
            }
        }

        return false;
    }

    public function __toString()
    {
        return $this->className . "::" . $this->methodName . "::" . get_class($this->annotationForMethod);
    }
}