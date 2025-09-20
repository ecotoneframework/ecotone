<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use function array_merge;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\EventSourcing\Attribute\ProjectionDelete;
use Ecotone\EventSourcing\Attribute\ProjectionInitialization;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\NamedEvent;
use Ecotone\Projecting\Attribute\Projection;
use LogicException;

/**
 * This module register projection based on attributes
 */
#[ModuleAnnotation]
class ProjectingAttributeModule implements AnnotationModule
{
    /**
     * @param EcotoneProjectionExecutorBuilder[] $projectionBuilders
     * @param MessageProcessorActivatorBuilder[] $lifecycleHandlers
     */
    public function __construct(
        private array $projectionBuilders = [],
        private array $lifecycleHandlers = []
    ) {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $namedEvents = [];
        foreach ($annotationRegistrationService->findAnnotatedClasses(NamedEvent::class) as $className) {
            $attribute = $annotationRegistrationService->getAttributeForClass($className, NamedEvent::class);
            $namedEvents[$className] = $attribute->getName();
        }

        $projectionBuilders = [];
        foreach ($annotationRegistrationService->findAnnotatedClasses(Projection::class) as $projectionClassName) {
            $projectionAttribute = $annotationRegistrationService->getAttributeForClass($projectionClassName, Projection::class);
            $projectionBuilder = new EcotoneProjectionExecutorBuilder($projectionAttribute->name, $projectionAttribute->partitionHeaderName, $namedEvents);

            $asynchronousChannelName = self::getProjectionAsynchronousChannel($annotationRegistrationService, $projectionClassName);
            if ($asynchronousChannelName !== null) {
                $projectionBuilder->setAsyncChannel($asynchronousChannelName);
            }
            $projectionBuilders[$projectionAttribute->name] = $projectionBuilder;
        }

        /** @var array<string, EcotoneProjectionExecutorBuilder> $projectionBuilders */
        $lifecycleHandlers = [];
        foreach ($annotationRegistrationService->findCombined(Projection::class, EventHandler::class) as $projectionEventHandler) {
            /** @var Projection $projectionAttribute */
            $projectionAttribute = $projectionEventHandler->getAnnotationForClass();
            $projectionBuilder = $projectionBuilders[$projectionAttribute->name] ?? throw new LogicException();
            $projectionBuilder->addEventHandler($projectionEventHandler);
        }

        $lifecycleAnnotations = array_merge(
            $annotationRegistrationService->findCombined(Projection::class, ProjectionInitialization::class),
            $annotationRegistrationService->findCombined(Projection::class, ProjectionDelete::class),
        );
        foreach ($lifecycleAnnotations as $lifecycleAnnotation) {
            /** @var Projection $projectionAttribute */
            $projectionAttribute = $lifecycleAnnotation->getAnnotationForClass();
            $projectionBuilder = $projectionBuilders[$projectionAttribute->name] ?? throw new LogicException();
            $projectionReferenceName = AnnotatedDefinitionReference::getReferenceForClassName($annotationRegistrationService, $lifecycleAnnotation->getClassName());
            $inputChannel = 'projecting_lifecycle_handler:' . $projectionAttribute->name . ':' . $lifecycleAnnotation->getMethodName();
            if ($lifecycleAnnotation->getAnnotationForMethod() instanceof ProjectionInitialization) {
                $projectionBuilder->setInitChannel($inputChannel);
            } elseif ($lifecycleAnnotation->getAnnotationForMethod() instanceof ProjectionDelete) {
                $projectionBuilder->setDeleteChannel($inputChannel);
            }


            $lifecycleHandlers[] = MessageProcessorActivatorBuilder::create()
                ->chainInterceptedProcessor(
                    MethodInvokerBuilder::create(
                        new Reference($projectionReferenceName),
                        InterfaceToCallReference::create($lifecycleAnnotation->getClassName(), $lifecycleAnnotation->getMethodName())
                    )
                )
                ->withInputChannelName($inputChannel);
        }

        return new self(array_values($projectionBuilders), $lifecycleHandlers);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->lifecycleHandlers as $lifecycleHandler) {
            $messagingConfiguration->registerMessageHandler($lifecycleHandler);
        }
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return $this->projectionBuilders;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    /**
     * @param class-string $projectionClassName
     */
    private static function getProjectionAsynchronousChannel(AnnotationFinder $annotationRegistrationService, string $projectionClassName): ?string
    {
        $attributes = $annotationRegistrationService->getAnnotationsForClass($projectionClassName);
        foreach ($attributes as $attribute) {
            if ($attribute instanceof Asynchronous) {
                $asynchronousChannelName = $attribute->getChannelName();
                Assert::isTrue(count($asynchronousChannelName) === 1, "Make use of single channel name in Asynchronous annotation for Projection: {$projectionClassName}");
                return array_pop($asynchronousChannelName);
            }
        }
        return null;
    }
}
