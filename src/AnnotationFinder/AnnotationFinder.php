<?php

namespace Ecotone\AnnotationFinder;

use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
interface AnnotationFinder extends AnnotationResolver
{
    /**
     * @template TClassAttribute of object
     * @template TMethodAttribute of object
     * @param class-string<TClassAttribute> $classAnnotationName
     * @param class-string<TMethodAttribute> $methodAnnotationClassName
     * @return list<AnnotatedDefinition<TClassAttribute,TMethodAttribute>>
     */
    public function findCombined(string $classAnnotationName, string $methodAnnotationClassName): array;

    /**
     * @return class-string[]
     */
    public function findAnnotatedClasses(string $annotationClassName): array;

    /**
     * @return AnnotatedMethod[]
     */
    public function findAnnotatedMethods(string $methodAnnotationClassName): array;

    /**
     * @template T of object
     * @param class-string<T> $attributeClassName
     * @return T
     * @throws InvalidArgumentException
     */
    public function getAttributeForClass(string $className, string $attributeClassName): object;
    public function findAttributeForClass(string $className, string $attributeClassName): ?object;
}
