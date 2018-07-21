<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleExtensionAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Module;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleRetrievingService;

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
     * @var ModuleExtensionAnnotation[]|Module[]
     */
    private $registeredModules = [];

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
     * @param $annotationClassName
     * @return array
     */
    private function createAnnotationClasses($annotationClassName): array
    {
        $moduleClassNames = $this->annotationRegistrationService->getAllClassesWithAnnotation($annotationClassName);

        $modules = [];
        /** @var AnnotationModule|string $moduleClassName */
        foreach ($moduleClassNames as $moduleClassName) {
            if (array_key_exists($moduleClassName, $this->registeredModules)) {
                $modules[] = $this->registeredModules[$moduleClassName];
                continue;
            }

            $this->registeredModules[$moduleClassName] = ($moduleClassName)::create($this->annotationRegistrationService);
            $modules[] = $this->registeredModules[$moduleClassName];
        }

        return $modules;
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleExtensionConfigurations(): array
    {
        return $this->createAnnotationClasses(ModuleExtensionAnnotation::class);
    }
}