<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleMessagingConfiguration;

/**
 * Interface AnnotationConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AnnotationConfiguration extends ModuleMessagingConfiguration
{
    /**
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @param ClassLocator $classLocator
     * @param ClassMetadataReader $classMetadataReader
     * @return AnnotationConfiguration
     */
    public static function createAnnotationConfiguration(ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader) : AnnotationConfiguration;
}