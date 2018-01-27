<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 27.01.18
 * Time: 19:07
 */

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;


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