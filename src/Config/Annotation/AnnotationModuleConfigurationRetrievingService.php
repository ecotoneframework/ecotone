<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationExtensionAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\AnnotationRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleMessagingConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class AnnotationModuleConfigurationRetrievingService
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationModuleConfigurationRetrievingService implements ModuleConfigurationRetrievingService
{
    /**
     * @var ClassLocator
     */
    private $classLocator;
    /**
     * @var ClassMetadataReader
     */
    private $classMetadataReader;

    /**
     * AnnotationModuleConfigurationRetrievingService constructor.
     * @param ClassLocator $classLocator
     * @param ClassMetadataReader $classMetadataReader
     */
    public function __construct(ClassLocator $classLocator, ClassMetadataReader $classMetadataReader)
    {
        $this->classLocator = $classLocator;
        $this->classMetadataReader = $classMetadataReader;
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleConfigurations(): array
    {
        $configurationClassNames = $this->classLocator->getAllClassesWithAnnotation(ModuleConfigurationAnnotation::class);
        $configurationClasses = [];
        $configurationExtensionClassNames = $this->classLocator->getAllClassesWithAnnotation(ModuleConfigurationExtensionAnnotation::class);



        foreach ($configurationClassNames as $configurationClassesName) {
            /** @var ModuleConfigurationAnnotation $annotation */
            $annotation = $this->classMetadataReader->getAnnotationForClass($configurationClassesName, ModuleConfigurationAnnotation::class);

            if (class_implements($configurationClassesName, AnnotationConfiguration::class)) {
//                $configurationClass::createAnnotationConfiguration()
            }else if (class_implements($configurationClassesName, ModuleMessagingConfiguration::class)) {
//                $configurationClassesName::
            }else {
                throw new InvalidArgumentException("Can't register module {$configurationClassesName} it doesn't extend " . ModuleMessagingConfiguration::class);
            }
        }

        return $configurationClasses;
    }
}