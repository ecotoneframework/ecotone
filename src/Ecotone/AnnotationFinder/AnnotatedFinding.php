<?php

namespace Ecotone\AnnotationFinder;

interface AnnotatedFinding
{
    public function getAnnotationForMethod() : object;

    public function getClassName(): string;

    public function getMethodName(): string;

    /**
     * @return object[]
     */
    public function getClassAnnotations() : array;

    /**
     * @return object[]
     */
    public function getMethodAnnotations(): array;

    /**
     * @return object[]
     */
    public function getMethodAnnotationsWithType(string $type) : array;

    /**
     * @return object[]
     */
    public function getClassAnnotationsWithType(string $type) : array;

    public function hasMethodAnnotation(string $type) : bool;

    public function hasClassAnnotation(string $type) : bool;
}