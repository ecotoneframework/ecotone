<?php

namespace Ecotone\AnnotationFinder;

use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
interface AnnotationFinder extends AnnotationResolver
{
    /**
     * @return AnnotatedDefinition[]
     */
    public function findCombined(string $classAnnotationName, string $methodAnnotationClassName): array;

    /**
     * @return string[]
     */
    public function findAnnotatedClasses(string $annotationClassName): array;

    /**
     * @return AnnotatedMethod[]
     */
    public function findAnnotatedMethods(string $methodAnnotationClassName): array;

    /**
     * @template T
     * @param class-string<T> $attributeClassName
     * @return T
     * @throws InvalidArgumentException
     */
    public function getAttributeForClass(string $className, string $attributeClassName): object;
    public function findAttributeForClass(string $className, string $attributeClassName): ?object;
}
