<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfigurationRetrievingService;

/**
 * Class AnnotationModuleConfigurationRetrievingService
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationModuleConfigurationRetrievingService implements ModuleConfigurationRetrievingService
{
    /**
     * @var ClassLocator
     */
    private $classLocator;

    /**
     * AnnotationModuleConfigurationRetrievingService constructor.
     * @param ClassLocator $classLocator
     */
    public function __construct(ClassLocator $classLocator)
    {
        $this->classLocator = $classLocator;
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleConfigurations(): array
    {
        $configurationClassesNames = $this->classLocator->getAllClassesWithAnnotation(ModuleConfiguration::class);
        $configurationClasses = [];

        foreach ($configurationClassesNames as $configurationClassesName) {
            $configurationClasses[] = new $configurationClassesName();
        }

        foreach ($configurationClasses as $configurationClass) {
            if ($configurationClass instanceof AnnotationConfiguration) {
                $configurationClass->setClassLocator($this->classLocator);
            }
        }

        return $configurationClasses;
    }
}