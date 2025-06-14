<?php

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Precedence;
use Ecotone\Modelling\CommandBus;
use Ramsey\Uuid\Uuid;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class InstantRetryModule implements AnnotationModule
{
    private function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $configuration = ExtensionObjectResolver::resolveUnique(InstantRetryConfiguration::class, $extensionObjects, InstantRetryConfiguration::createWithDefaults());
        $messagingConfiguration->registerServiceDefinition(RetryStatusTracker::class, Definition::createFor(RetryStatusTracker::class, [false]));

        if ($configuration->isEnabledForCommandBus()) {
            $this->registerInterceptor($messagingConfiguration, $interfaceToCallRegistry, $configuration->getCommandBusRetryTimes(), $configuration->getCommandBuExceptions(), CommandBus::class, Precedence::GLOBAL_INSTANT_RETRY_PRECEDENCE);
        }
        if ($configuration->isEnabledForAsynchronousEndpoints()) {
            $this->registerInterceptor($messagingConfiguration, $interfaceToCallRegistry, $configuration->getAsynchronousRetryTimes(), $configuration->getAsynchronousExceptions(), AsynchronousRunningEndpoint::class, Precedence::GLOBAL_INSTANT_RETRY_PRECEDENCE);
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof InstantRetryConfiguration;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return [];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    /**
     * @param string[] $exceptions
     */
    private function registerInterceptor(
        Configuration $messagingConfiguration,
        InterfaceToCallRegistry $interfaceToCallRegistry,
        int $retryAttempt,
        array $exceptions,
        string $pointcut,
        int $precedence,
    ): void {
        $instantRetryId = Uuid::uuid4()->toString();
        $messagingConfiguration->registerServiceDefinition($instantRetryId, Definition::createFor(InstantRetryInterceptor::class, [$retryAttempt, $exceptions, Reference::to(RetryStatusTracker::class)]));

        $messagingConfiguration
            ->registerAroundMethodInterceptor(
                AroundInterceptorBuilder::create(
                    $instantRetryId,
                    $interfaceToCallRegistry->getFor(InstantRetryInterceptor::class, 'retry'),
                    $precedence,
                    $pointcut
                )
            );
    }
}
