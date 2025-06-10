<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\ChangingHeaders;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\Config\Routing\RoutingEvent;
use Ecotone\Modelling\Config\Routing\RoutingEventHandler;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class ServiceHandlerModule implements AnnotationModule, RoutingEventHandler
{
    private function __construct(private InterfaceToCallRegistry $interfaceToCallRegistry)
    {
    }

    /**
     * In here we should provide messaging component for module
     *
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self($interfaceToCallRegistry);
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
        return [$this];
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $moduleExtensions, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
    }

    public function handleRoutingEvent(RoutingEvent $event): void
    {
        $registration = $event->getRegistration();
        if ($registration->hasClassAnnotation(Aggregate::class)) {
            return;
        }

        /** @var QueryHandler|CommandHandler|EventHandler $methodAnnotation */
        $methodAnnotation = $registration->getAnnotationForMethod();
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $relatedClassInterface = $this->interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        $parameterConverters = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface);

        $handler = $registration->hasMethodAnnotation(ChangingHeaders::class)
            ? TransformerBuilder::create(AnnotatedDefinitionReference::getReferenceFor($registration), $this->interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName()))
            : ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($registration), $this->interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName()));

        $event->getBusRoutingMapBuilder()->getMessagingConfiguration()->registerMessageHandler(
            $handler
                ->withInputChannelName($event->getDestinationChannel())
                ->withOutputMessageChannel($methodAnnotation->getOutputChannelName())
                ->withEndpointId($methodAnnotation->getEndpointId())
                ->withMethodParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($methodAnnotation->getRequiredInterceptorNames())
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
