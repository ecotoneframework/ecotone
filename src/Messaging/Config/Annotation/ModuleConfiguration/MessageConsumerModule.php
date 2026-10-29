<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\MessageConsumer;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class MessageConsumerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    private function __construct(private array $serviceActivators)
    {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $annotationParameterBuilder = ParameterConverterAnnotationFactory::create();
        $amqpConsumers = $annotationRegistrationService->findAnnotatedMethods(MessageConsumer::class);

        $serviceActivators = [];

        foreach ($amqpConsumers as $amqpConsumer) {
            $reference = AnnotatedDefinitionReference::getReferenceFor($amqpConsumer);
            /** @var MessageConsumer $amqpConsumerAnnotation */
            $amqpConsumerAnnotation = $amqpConsumer->getAnnotationForMethod();

            $endpointId = $amqpConsumerAnnotation->getEndpointId();
            $serviceActivators[] = ServiceActivatorBuilder::create($reference, $interfaceToCallRegistry->getFor($amqpConsumer->getClassName(), $amqpConsumer->getMethodName()))
                ->withEndpointId($endpointId . '.target')
                ->withInputChannelName($endpointId)
                ->withMethodParameterConverters($annotationParameterBuilder->createParameterWithDefaults(
                    $interfaceToCallRegistry->getFor($amqpConsumer->getClassName(), $amqpConsumer->getMethodName()),
                ));
        }

        return new self($serviceActivators);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->serviceActivators as $serviceActivator) {
            $messagingConfiguration->registerMessageHandler($serviceActivator);
        }
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
