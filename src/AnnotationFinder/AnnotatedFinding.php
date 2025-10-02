<?php

namespace Ecotone\AnnotationFinder;

use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
interface AnnotatedFinding
{
    public function getAnnotationForMethod(): object;

    public function getClassName(): string;

    public function getMethodName(): string;

    public function isMagicMethod(): bool;

    /**
     * @return object[]
     */
    public function getClassAnnotations(): array;

    /**
     * @return object[]
     */
    public function getMethodAnnotations(): array;

    /**
     * @return object[]
     */
    public function getMethodAnnotationsWithType(string $type): array;

    /**
     * Priority is method annotations over class annotations
     *
     * @return object[]
     */
    public function getAnnotationsByImportanceOrder(string $type): array;

    /**
     * @return object[]
     */
    public function getClassAnnotationsWithType(string $type): array;

    public function hasMethodAnnotation(string $type): bool;

    public function hasClassAnnotation(string $type): bool;

    public function hasAnnotation(string|Type $type): bool;
}
