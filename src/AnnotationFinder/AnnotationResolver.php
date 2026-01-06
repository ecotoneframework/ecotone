<?php

namespace Ecotone\AnnotationFinder;

/**
 * licence Apache-2.0
 */
interface AnnotationResolver
{
    /**
     * @return object[]
     */
    public function getAnnotationsForMethod(string $className, string $methodName): array;

    /**
     * @template T of object
     * @param class-string $className
     * @param class-string<T>|null $attributeClassName
     * @return ($attributeClassName is null ? list<object> : list<T>)
     */
    public function getAnnotationsForClass(string $className, ?string $attributeClassName = null): array;

    /**
     * @return object[]
     */
    public function getAnnotationsForProperty(string $className, string $propertyName): array;
}
