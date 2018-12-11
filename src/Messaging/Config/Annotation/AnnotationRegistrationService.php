<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation;

use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Interface AnnotationRegistrator
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AnnotationRegistrationService
{
    /**
     * @param string $classAnnotationName
     * @param string $methodAnnotationClassName
     * @return AnnotationRegistration[]
     */
    public function findRegistrationsFor(string $classAnnotationName, string $methodAnnotationClassName) : array;

    /**
     * @param string $annotationClassName
     * @return string[]
     */
    public function getAllClassesWithAnnotation(string $annotationClassName): array;

    /**
     * @param string $className
     * @param string $annotationClassName
     * @return object return annotation class can be only single of specific type
     * @throws InvalidArgumentException if annotation not found in class
     */
    public function getAnnotationForClass(string $className, string $annotationClassName);
}