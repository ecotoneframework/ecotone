<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

/**
 * Class ClassLocator
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ClassLocator
{
    /**
     * @return string[]
     */
    public function getAllClasses(): array;

    /**
     * @param string $annotationName
     * @return string[]
     */
    public function getAllClassesWithAnnotation(string $annotationName): array;
}