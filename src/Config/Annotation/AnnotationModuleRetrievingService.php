<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ConfigurationVariableAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleExtensionAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\RequiredReferenceAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariable;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\RequiredReference;

/**
 * Class AnnotationModuleConfigurationRetrievingService
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationModuleRetrievingService implements ModuleRetrievingService
{
    /**
     * @var AnnotationRegistrationService
     */
    private $annotationRegistrationService;

    /**
     * AnnotationModuleConfigurationRetrievingService constructor.
     * @param AnnotationRegistrationService $annotationRegistrationService
     */
    public function __construct(AnnotationRegistrationService $annotationRegistrationService)
    {
        $this->annotationRegistrationService = $annotationRegistrationService;
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleConfigurations(): array
    {
        return $this->createAnnotationClasses(ModuleAnnotation::class);
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleExtensionConfigurations(): array
    {
        return $this->createAnnotationClasses(ModuleExtensionAnnotation::class);
    }

    /**
     * @param $annotationClassName
     * @return array
     */
    private function createAnnotationClasses($annotationClassName): array
    {
        $moduleClassNames = $this->annotationRegistrationService->getAllClassesWithAnnotation($annotationClassName);

        $modules = [];
        /** @var AnnotationModule $moduleClassName */
        foreach ($moduleClassNames as $moduleClassName) {
            $modules[] = ($moduleClassName)::create($this->annotationRegistrationService);
        }

        return $modules;
    }
}