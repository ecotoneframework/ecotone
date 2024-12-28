<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\StreamBasedSource;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\PriorityBasedOnType;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\ChangingHeaders;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class ServiceHandlerModule implements AnnotationModule
{
    /**
     * @param AnnotatedFinding[] $serviceCommandHandlers
     * @param AnnotatedFinding[] $serviceQueryHandlers
     * @param AnnotatedFinding[] $serviceEventHandlers
     */
    private function __construct(
        private array $serviceCommandHandlers,
        private array $serviceQueryHandlers,
        private array $serviceEventHandlers,
    ) {
    }

    /**
     * In here we should provide messaging component for module
     *
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self(
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(CommandHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return ! $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(QueryHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return ! $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
            array_filter(
                $annotationRegistrationService->findAnnotatedMethods(EventHandler::class),
                function (AnnotatedFinding $annotatedFinding) {
                    return ! $annotatedFinding->hasClassAnnotation(Aggregate::class);
                }
            ),
        );
    }

    public static function getHandlerChannel(AnnotatedFinding $registration): string
    {
        /** @var EndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        return $annotationForMethod->getEndpointId() . '.target';
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

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $moduleExtensions, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->serviceCommandHandlers as $registration) {
            $this->registerServiceHandler(MessageHandlerRoutingModule::getRoutingInputMessageChannelFor($registration, $interfaceToCallRegistry), $messagingConfiguration, $registration, $interfaceToCallRegistry, false);
        }
        foreach ($this->serviceQueryHandlers as $registration) {
            $this->registerServiceHandler(MessageHandlerRoutingModule::getRoutingInputMessageChannelFor($registration, $interfaceToCallRegistry), $messagingConfiguration, $registration, $interfaceToCallRegistry, false);
        }
        foreach ($this->serviceEventHandlers as $registration) {
            $this->registerServiceHandler(MessageHandlerRoutingModule::getRoutingInputMessageChannelForEventHandler($registration, $interfaceToCallRegistry), $messagingConfiguration, $registration, $interfaceToCallRegistry, $registration->hasClassAnnotation(StreamBasedSource::class));
        }
    }

    private function registerServiceHandler(string $inputChannelNameForRouting, Configuration $configuration, AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry, bool $isStreamBasedSource): void
    {
        /** @var QueryHandler|CommandHandler|EventHandler $methodAnnotation */
        $methodAnnotation = $registration->getAnnotationForMethod();
        $executionInputChannel = MessageHandlerRoutingModule::getExecutionMessageHandlerChannel($registration);
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        $relatedClassInterface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
        $parameterConverters = $parameterConverterAnnotationFactory->createParameterWithDefaults($relatedClassInterface);

        $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelNameForRouting));
        /**
         * We want to connect Event Handler directly to Event Bus channel only if it's not fetched from Stream Based Source.
         * This allows to connecting Event Handlers via Projection Event Handler that lead the way.
         */
        if (! $isStreamBasedSource) {
            $configuration->registerMessageHandler(
                BridgeBuilder::create()
                    ->withInputChannelName($inputChannelNameForRouting)
                    ->withOutputMessageChannel($executionInputChannel)
                    ->withEndpointAnnotations([PriorityBasedOnType::fromAnnotatedFinding($registration)->toAttributeDefinition()])
            );
        }

        $handler = $registration->hasMethodAnnotation(ChangingHeaders::class)
            ? TransformerBuilder::create(AnnotatedDefinitionReference::getReferenceFor($registration), $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName()))
            : ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($registration), $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName()));

        $configuration->registerMessageHandler(
            $handler
                ->withInputChannelName($executionInputChannel)
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
