<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\Serialization;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Channel\MessageChannelWithSerializationBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
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
            if (! $pollableMessageChannel->isPollable()) {
                continue;
            }

            $messagingConfiguration->registerChannelInterceptor(
                new OutboundSerializationChannelBuilder(
                    $pollableMessageChannel->getMessageChannelName(),
                    $pollableMessageChannel->getHeaderMapper(),
                    $pollableMessageChannel->getConversionMediaType()
                )
            );
        }
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
