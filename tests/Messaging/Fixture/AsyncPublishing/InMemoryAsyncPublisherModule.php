<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\AsyncPublishing;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\AsyncPublishing\Config\AsyncPublishGatewayRegistration;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class InMemoryAsyncPublisherModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const PUBLISHER_REFERENCE = 'inMemoryAsyncPublisher';

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $publisherReference = self::PUBLISHER_REFERENCE;

        AsyncPublishGatewayRegistration::registerFor($messagingConfiguration, $publisherReference);

        $messagingConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($publisherReference))
            ->registerMessageHandler(
                ServiceActivatorBuilder::create(InMemoryAsyncOutboundAdapter::class, 'handle')
                    ->withInputChannelName($publisherReference)
                    ->withEndpointId($publisherReference . '.handler')
            );
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
