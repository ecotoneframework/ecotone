<?php

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\InstantRetry;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Messaging\Attribute\MessageConsumer;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\Config\DatabaseTransaction\TransactionStatusTracker;
use Symfony\Component\Uid\Uuid;

#[ModuleAnnotation]
/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class InstantRetryAttributeModule implements AnnotationModule
{
    /**
     * @var array<string, InstantRetry> $commandBusesWithInstantRetry key is interface name, value is InstantRetry attribute
     */
    private array $commandBusesWithInstantRetry;
    /**
     * @var array<string, InstantRetry> $asynchronousEndpointsWithInstantRetry key is endpoint id
     */
    private array $asynchronousEndpointsWithInstantRetry;

    private function __construct(array $commandBusesWithInstantRetry, array $asynchronousEndpointsWithInstantRetry)
    {
        $this->commandBusesWithInstantRetry = $commandBusesWithInstantRetry;
        $this->asynchronousEndpointsWithInstantRetry = $asynchronousEndpointsWithInstantRetry;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $commandBusesWithInstantRetry = [];
        $annotatedInterfaces = $annotationRegistrationService->findAnnotatedClasses(InstantRetry::class);

        foreach ($annotatedInterfaces as $annotatedInterface) {
            if (! is_subclass_of($annotatedInterface, CommandBus::class)) {
                throw new ConfigurationException(sprintf(
                    "InstantRetry attribute can only be used on interfaces extending CommandBus. '%s' does not extend CommandBus.",
                    $annotatedInterface
                ));
            }

            $instantRetryAttribute = $annotationRegistrationService->getAttributeForClass($annotatedInterface, InstantRetry::class);
            $commandBusesWithInstantRetry[$annotatedInterface] = $instantRetryAttribute;
        }

        $asynchronousEndpointsWithInstantRetry = [];
        $annotatedMethods = $annotationRegistrationService->findAnnotatedMethods(InstantRetry::class);
        foreach ($annotatedMethods as $annotatedMethod) {
            if (! $annotatedMethod->hasMethodAnnotation(MessageConsumer::class)) {
                throw new ConfigurationException(\Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ErrorChannelExceptionMessages::instantRetryNotOnInboundChannelAdapter(
                    $annotatedMethod->getClassName(),
                    $annotatedMethod->getMethodName(),
                ));
            }

            /** @var MessageConsumer $consumerAttribute */
            $consumerAttribute = $annotatedMethod->getMethodAnnotationsWithType(MessageConsumer::class)[0];
            $asynchronousEndpointsWithInstantRetry[$consumerAttribute->getEndpointId()] = $annotatedMethod->getAnnotationForMethod();
        }

        return new self($commandBusesWithInstantRetry, $asynchronousEndpointsWithInstantRetry);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (empty($this->commandBusesWithInstantRetry) && empty($this->asynchronousEndpointsWithInstantRetry)) {
            return;
        }

        if (! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            throw LicensingException::create(\Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ErrorChannelExceptionMessages::instantRetryRequiresEnterprise());
        }

        // Register interceptors for interfaces with InstantRetry attribute
        foreach ($this->commandBusesWithInstantRetry as $commandBusInterface => $instantRetryAttribute) {
            $this->registerInterceptor(
                $messagingConfiguration,
                $interfaceToCallRegistry,
                $instantRetryAttribute->retryTimes,
                $instantRetryAttribute->exceptions,
                Type::object($commandBusInterface)->toString(),
                Precedence::CUSTOM_INSTANT_RETRY_PRECEDENCE,
                null,
            );
        }

        foreach ($this->asynchronousEndpointsWithInstantRetry as $asynchronousEndpoint => $instantRetryAttribute) {
            $this->registerInterceptor(
                $messagingConfiguration,
                $interfaceToCallRegistry,
                $instantRetryAttribute->retryTimes,
                $instantRetryAttribute->exceptions,
                AsynchronousRunningEndpoint::class,
                Precedence::CUSTOM_INSTANT_RETRY_PRECEDENCE,
                $asynchronousEndpoint,
            );
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
        ?string $relatedEndpointId,
    ): void {
        $instantRetryId = Uuid::v7()->toRfc4122();
        $messagingConfiguration->registerServiceDefinition($instantRetryId, Definition::createFor(InstantRetryInterceptor::class, [$retryAttempt, $exceptions, Reference::to(RetryStatusTracker::class), Reference::to(TransactionStatusTracker::class), $relatedEndpointId]));

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
