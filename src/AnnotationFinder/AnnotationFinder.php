<?php

namespace Ecotone\AnnotationFinder;

interface AnnotationFinder extends AnnotationResolver
{
    /**
     * @return AnnotatedDefinition[]
     */
    public function findCombined(string $classAnnotationName, string $methodAnnotationClassName) : array;

    /**
     * @return string[]
     */
    public function findAnnotatedClasses(string $annotationClassName): array;

    /**
     * @return AnnotatedMethod[]
     */
    public function findAnnotatedMethods(string $methodAnnotationClassName) : array;
}