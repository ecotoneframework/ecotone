<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ConfigurationVariable;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Module;
use Ecotone\Messaging\Config\ModuleRetrievingService;
use Ecotone\Messaging\Config\ServiceConfigurationFromServiceContext;
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
        $serviceConfigurationOrigin = null;

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

                $extensionObjectToResolve = [$extensionObjectToResolve];
            }

            foreach ($extensionObjectToResolve as $singleMessagingComponent) {
                Assert::isObject($singleMessagingComponent, "Incorrect configuration given in {$annotationRegistration->getClassName()}:{$annotationRegistration->getMethodName()}. Configuration returned by ServiceContext must be object or array of objects.");

                if ($singleMessagingComponent instanceof ServiceConfiguration) {
                    $singleMessagingComponent = new ServiceConfigurationFromServiceContext($singleMessagingComponent, $annotationRegistration->getClassName(), $annotationRegistration->getMethodName());
                    $this->refuseSecondServiceConfiguration($serviceConfigurationOrigin, $singleMessagingComponent);
                    $serviceConfigurationOrigin = $singleMessagingComponent;
                }

                $extensionObjects[] = $singleMessagingComponent;
            }
        }

        return $extensionObjects;
    }

    private function refuseSecondServiceConfiguration(?ServiceConfigurationFromServiceContext $first, ServiceConfigurationFromServiceContext $second): void
    {
        if ($first === null) {
            return;
        }

        throw ConfigurationException::create(
            "Ecotone was given more than one ServiceConfiguration: {$first->origin()} and {$second->origin()} each return one. "
            . ServiceConfigurationFromServiceContext::ONLY_ONE_IS_READ
            . 'Keep one #[ServiceContext] method returning ServiceConfiguration::createWithDefaults() and move every with*() call onto it.'
        );
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
