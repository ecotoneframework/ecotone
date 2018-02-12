<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

/**
 * Class ClassLocator
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
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