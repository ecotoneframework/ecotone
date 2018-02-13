<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleMessagingConfiguration;

/**
 * Interface AnnotationConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration
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