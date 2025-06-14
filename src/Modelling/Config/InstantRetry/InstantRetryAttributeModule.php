<?php

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\Attribute\InstantRetry;
use Ecotone\Modelling\CommandBus;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

#[ModuleAnnotation]
/**
 * licence Enterprise
 */
final class InstantRetryAttributeModule implements AnnotationModule
{
    private array $commandBusesWithInstantRetry;

    private function __construct(array $commandBusesWithInstantRetry)
    {
        $this->commandBusesWithInstantRetry = $commandBusesWithInstantRetry;
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
                throw new InvalidArgumentException(sprintf(
                    "InstantRetry attribute can only be used on interfaces extending CommandBus. '%s' does not extend CommandBus.",
                    $annotatedInterface
                ));
            }

            $instantRetryAttribute = $annotationRegistrationService->getAttributeForClass($annotatedInterface, InstantRetry::class);
            $commandBusesWithInstantRetry[$annotatedInterface] = $instantRetryAttribute;
        }

        return new self($commandBusesWithInstantRetry);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (empty($this->commandBusesWithInstantRetry)) {
            return;
        }

        if (! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            throw LicensingException::create('Instant retry attribute is available only for Ecotone Enterprise.');
        }

        // Register interceptors for interfaces with InstantRetry attribute
        foreach ($this->commandBusesWithInstantRetry as $commandBusInterface => $instantRetryAttribute) {
            $this->registerInterceptor(
                $messagingConfiguration,
                $interfaceToCallRegistry,
                $instantRetryAttribute->retryTimes,
                $instantRetryAttribute->exceptions,
                TypeDescriptor::create($commandBusInterface)->toString(),
                Precedence::CUSTOM_INSTANT_RETRY_PRECEDENCE,
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
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
