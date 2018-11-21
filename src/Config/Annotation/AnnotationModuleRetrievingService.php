<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Extension;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
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
     * @var Module[]
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
    public function findAllExtensionObjects(): array
    {
        $extensionObjectsRegistrations = $this->annotationRegistrationService->findRegistrationsFor(ApplicationContext::class, Extension::class);
        $extensionObjects = [];

        $classes = [];
        foreach ($extensionObjectsRegistrations as $annotationRegistration) {
            if (!array_key_exists($annotationRegistration->getClassName(), $classes)) {
                $classToInstantiate = $annotationRegistration->getClassName();
                $classes[$annotationRegistration->getClassName()] = new $classToInstantiate();
            }

            $classToRun = $classes[$annotationRegistration->getClassName()];
            $extensionObjectToResolve = $classToRun->{$annotationRegistration->getMethodName()}();

            if (!is_array($extensionObjectToResolve)) {
                $extensionObjects[] = $extensionObjectToResolve;
                continue;
            }

            foreach ($extensionObjectToResolve as $singleMessagingComponent) {
                $extensionObjects[] = $singleMessagingComponent;
            }
        }

        return $extensionObjects;
    }
}