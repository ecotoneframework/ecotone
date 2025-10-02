<?php

namespace Ecotone\AnnotationFinder;

use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Handler\Type;
use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
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
    public static function create(object $annotationForMethod, string $className, string $methodName, array $classAnnotations, array $methodAnnotations): self
    {
        return new self($annotationForMethod, $className, $methodName, $classAnnotations, $methodAnnotations);
    }

    public function getAnnotationForMethod(): object
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

    public function isMagicMethod(): bool
    {
        return str_starts_with($this->methodName, '__');
    }

    /**
     * @return object[]
     */
    public function getMethodAnnotations(): array
    {
        return $this->methodAnnotations;
    }

    /**
     * @return AttributeDefinition[]
     */
    public function getAllAnnotationDefinitions(): array
    {
        $annotations = [];
        foreach ($this->classAnnotations as $endpointAnnotation) {
            if ($this->hasMethodAnnotation($endpointAnnotation->getClassName())) {
                continue;
            }

            $annotations[] = AttributeDefinition::fromObject($endpointAnnotation);
        }

        foreach ($this->methodAnnotations as $methodAnnotation) {
            $annotations[] = AttributeDefinition::fromObject($methodAnnotation);
        }

        return $annotations;
    }

    public function hasMethodAnnotation(string|Type $type): bool
    {
        $type = $type instanceof Type ? $type->toString() : $type;

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
    public function getClassAnnotations(): array
    {
        return $this->classAnnotations;
    }

    /**
     * @return object[]
     * @throws InvalidArgumentException if not found any
     */
    public function getClassAnnotationsWithType(string $type): array
    {
        $annotations = [];
        foreach ($this->classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof $type) {
                $annotations[] = $classAnnotation;
            }
        }

        if (empty($annotations)) {
            throw new InvalidArgumentException("Trying to retrieve class annotation {$type}, but there is no any for {$this}");
        }

        return $annotations;
    }

    /**
     * @return object[]
     * @throws InvalidArgumentException if not found any
     */
    public function getMethodAnnotationsWithType(string $type): array
    {
        $annotations = [];
        foreach ($this->methodAnnotations as $methodAnnotation) {
            if ($methodAnnotation instanceof $type) {
                $annotations[] = $methodAnnotation;
            }
        }

        if (empty($annotations)) {
            throw new InvalidArgumentException("Trying to retrieve class annotation {$type}, but there is no any for {$this}");
        }

        return $annotations;
    }

    public function getAnnotationsByImportanceOrder(string|Type $type): array
    {
        $type = $type instanceof Type ? $type->toString() : $type;

        if ($this->hasMethodAnnotation($type)) {
            return $this->getMethodAnnotationsWithType($type);
        }

        return $this->getClassAnnotationsWithType($type);
    }

    public function hasClassAnnotation(string|Type $type): bool
    {
        $type = $type instanceof Type ? $type->toString() : $type;

        foreach ($this->classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof $type) {
                return true;
            }
        }

        return false;
    }

    public function hasAnnotation(string|Type $type): bool
    {
        return $this->hasClassAnnotation($type) || $this->hasMethodAnnotation($type);
    }

    public function __toString()
    {
        return $this->className . '::' . $this->methodName . '::' . get_class($this->annotationForMethod);
    }
}
