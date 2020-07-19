<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Module;
use Ecotone\Messaging\Config\ModuleRetrievingService;

/**
 * Class AnnotationModuleConfigurationRetrievingService
 * @package Ecotone\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationModuleRetrievingService implements ModuleRetrievingService
{
    private AnnotationFinder $annotationRegistrationService;
    /**
     * @var Module[]
     */
    private array $registeredModules = [];

    public function __construct(AnnotationFinder $annotationRegistrationService)
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
        $moduleClassNames = $this->annotationRegistrationService->findAnnotatedClasses($annotationClassName);

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
        $extensionObjectsRegistrations = $this->annotationRegistrationService->findAnnotatedMethods(ApplicationContext::class, Extension::class);
        $extensionObjects = [];

        $classes = [];
        foreach ($extensionObjectsRegistrations as $annotationRegistration) {
            if (!array_key_exists($annotationRegistration->getClassName(), $classes)) {
                $classToInstantiate = $annotationRegistration->getClassName();
                $reflectionClass = new \ReflectionClass($annotationRegistration->getClassName());
                if ($reflectionClass->hasMethod("__construct")  && $reflectionClass->getMethod("__construct")->getParameters()) {
                    throw ConfigurationException::create("{$annotationRegistration} should not contains any constructor parameters");
                }
                if ($reflectionClass->getMethod($annotationRegistration->getMethodName())->getParameters()) {
                    throw ConfigurationException::create("{$annotationRegistration} should not contains any parameters");
                }

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