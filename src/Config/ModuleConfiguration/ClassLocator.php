<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Class ClassLocator
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ClassLocator
{
    /**
     * @return array|\ReflectionClass[]
     */
    public function getAllClasses(): array;

    /**
     * @param string $annotationName
     * @return array|\ReflectionClass[]
     */
    public function getAllClassesWithAnnotation(string $annotationName): array;
}