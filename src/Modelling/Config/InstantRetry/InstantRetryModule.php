<?php

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Modelling\Config\DatabaseTransaction\TransactionStatusTracker;
use Symfony\Component\Uid\Uuid;

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
        $messagingConfiguration->registerServiceDefinition(TransactionStatusTracker::class, Definition::createFor(TransactionStatusTracker::class, [false]));

        if ($configuration->isEnabledForCommandBus) {
            $this->registerInterceptor($messagingConfiguration, $interfaceToCallRegistry, $configuration->commandBusRetryTimes, $configuration->commandBuExceptions, CommandBus::class, Precedence::GLOBAL_INSTANT_RETRY_PRECEDENCE);
        }
        if ($configuration->isEnabledForAsynchronousEndpoints) {
            $this->registerInterceptor($messagingConfiguration, $interfaceToCallRegistry, $configuration->asynchronousRetryTimes, $configuration->asynchronousExceptions, AsynchronousRunningEndpoint::class, Precedence::GLOBAL_INSTANT_RETRY_PRECEDENCE);
        }
    }

    /**
     * @inheritDoc
     */
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
        $instantRetryId = Uuid::v7()->toRfc4122();
        $messagingConfiguration->registerServiceDefinition($instantRetryId, Definition::createFor(InstantRetryInterceptor::class, [$retryAttempt, $exceptions, Reference::to(RetryStatusTracker::class), Reference::to(TransactionStatusTracker::class)]));

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
