<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

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
}