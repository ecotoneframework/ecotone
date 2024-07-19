<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\Serialization;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\MessageChannelWithSerializationBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class PollableChannelSerializationModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $pollableMessageChannels = ExtensionObjectResolver::resolve(MessageChannelWithSerializationBuilder::class, $extensionObjects);

        foreach ($pollableMessageChannels as $pollableMessageChannel) {
            $messagingConfiguration->registerChannelInterceptor(
                new OutboundSerializationChannelBuilder(
                    $pollableMessageChannel->getMessageChannelName(),
                    $pollableMessageChannel->getHeaderMapper(),
                    $pollableMessageChannel->getConversionMediaType()
                )
            );
        }
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof MessageChannelWithSerializationBuilder && $extensionObject->isPollable();
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::ASYNCHRONOUS_PACKAGE;
    }
}
