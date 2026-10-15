<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\SendRetries;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\DynamicMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Channel\PollableChannel\GlobalPollableChannelConfiguration;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
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
final class PollableChannelSendRetriesModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $globalPollableChannelConfiguration = ExtensionObjectResolver::resolveUnique(GlobalPollableChannelConfiguration::class, $extensionObjects, GlobalPollableChannelConfiguration::createWithDefaults());
        $pollableMessageChannels = ExtensionObjectResolver::resolve(MessageChannelBuilder::class, $extensionObjects);
        $pollableChannelConfigurations = ExtensionObjectResolver::resolve(PollableChannelConfiguration::class, $extensionObjects);

        foreach ($pollableMessageChannels as $pollableMessageChannel) {
            if (! $pollableMessageChannel->isPollable() || $pollableMessageChannel instanceof DynamicMessageChannelBuilder) {
                continue;
            }

            $channelConfiguration = $globalPollableChannelConfiguration;

            foreach ($pollableChannelConfigurations as $pollableChannelConfiguration) {
                if ($pollableChannelConfiguration->getChannelName() === $pollableMessageChannel->getMessageChannelName()) {
                    $channelConfiguration = $pollableChannelConfiguration;
                }
            }

            $messagingConfiguration->registerChannelInterceptor(
                new RetriesChannelInterceptorBuilder(
                    $pollableMessageChannel->getMessageChannelName(),
                    $channelConfiguration->getRetryTemplate(),
                    $channelConfiguration->getErrorChannelName()
                )
            );
        }
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
