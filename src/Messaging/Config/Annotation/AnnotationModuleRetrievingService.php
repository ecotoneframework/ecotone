<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\Parameter\ConfigurationVariable;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Module;
use Ecotone\Messaging\Config\ModuleRetrievingService;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Support\Assert;
use ReflectionClass;

/**
 * licence Apache-2.0
 */
class AnnotationModuleRetrievingService implements ModuleRetrievingService
{
    private AnnotationFinder $annotationRegistrationService;
    /**
     * @var Module[]
     */
    private array $registeredModules = [];
    private ConfigurationVariableService $variableConfigurationService;

    public function __construct(AnnotationFinder $annotationRegistrationService, private InterfaceToCallRegistry $interfaceToCallRegistry, ConfigurationVariableService $variableConfigurationService)
    {
        $this->annotationRegistrationService = $annotationRegistrationService;
        $this->variableConfigurationService = $variableConfigurationService;
    }

    /**
     * @inheritDoc
     */
    public function findAllModuleConfigurations(array $skippedModulePackageNames): array
    {
        return array_filter(
            $this->createAnnotationClasses(ModuleAnnotation::class),
            fn (Module $module) => ! in_array($module->getModulePackageName(), $skippedModulePackageNames)
        );
    }

    /**
     * @inheritDoc
     */
    public function findAllExtensionObjects(): array
    {
        $extensionObjectsRegistrations = $this->annotationRegistrationService->findAnnotatedMethods(ServiceContext::class);
        $extensionObjects              = [];

        foreach ($extensionObjectsRegistrations as $annotationRegistration) {
            $reflectionClass    = new ReflectionClass($annotationRegistration->getClassName());
            $interfaceToCall = InterfaceToCall::create(
                $annotationRegistration->getClassName(),
                $annotationRegistration->getMethodName()
            );
            if ($reflectionClass->hasMethod('__construct') && $reflectionClass->getMethod('__construct')->getParameters()) {
                throw ConfigurationException::create("{$annotationRegistration} should not contains any constructor parameters");
            }
            $newInstance = $reflectionClass->newInstance();

            $parameters = [];
            foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
                $variableName = $interfaceParameter->getName();
                if ($interfaceParameter->hasAnnotation(ConfigurationVariable::class)) {
                    /** @var ConfigurationVariable $variable */
                    $variable = $interfaceParameter->getAnnotationsOfType(ConfigurationVariable::class)[0];

                    $variableName = $variable->getName();
                }

                $parameters[] = $this->variableConfigurationService->getByName($variableName);
            }
            $extensionObjectToResolve = $newInstance->{$interfaceToCall->getMethodName()}(...$parameters);

            if (! is_array($extensionObjectToResolve)) {
                Assert::isObject($extensionObjectToResolve, "Incorrect configuration given in {$annotationRegistration->getClassName()}:{$annotationRegistration->getMethodName()}. Configuration returned by ServiceContext must be object or array of objects.");

                $extensionObjects[] = $extensionObjectToResolve;
                continue;
            }

            foreach ($extensionObjectToResolve as $singleMessagingComponent) {
                Assert::isObject($singleMessagingComponent, "Incorrect configuration given in {$annotationRegistration->getClassName()}:{$annotationRegistration->getMethodName()}. Configuration returned by ServiceContext must be object or array of objects.");

                $extensionObjects[] = $singleMessagingComponent;
            }
        }

        return $extensionObjects;
    }

    /**
     * @param $annotationClassName
     *
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

            $this->registeredModules[$moduleClassName] = ($moduleClassName)::create($this->annotationRegistrationService, $this->interfaceToCallRegistry);
            $modules[]                                 = $this->registeredModules[$moduleClassName];
        }

        return $modules;
    }
}
