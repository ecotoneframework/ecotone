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
     * @return object[]
     */
    public function getAnnotationsForClass(string $className): array;

    /**
     * @return object[]
     */
    public function getAnnotationsForProperty(string $className, string $propertyName): array;
}
