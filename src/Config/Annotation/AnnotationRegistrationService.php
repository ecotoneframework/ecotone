<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Interface AnnotationRegistrator
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
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