<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationReader;
use SimplyCodedSoftware\Messaging\Config\ModuleMessagingConfiguration;

/**
 * Interface AnnotationConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AnnotationConfiguration extends ModuleMessagingConfiguration
{
    /**
     * @param ClassLocator $classLocator
     */
    public function setClassLocator(ClassLocator $classLocator) : void;

    /**
     * @param ClassMetadataReader $classMetadataReader
     */
    public function setClassMetadataReader(ClassMetadataReader $classMetadataReader) : void;
}