<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Messaging\Channel\DeliveryConfirmation\DeliveryConfirmationInterceptor;
use Ecotone\Messaging\Channel\DeliveryConfirmation\PendingDeliveryRegistry;
use Ecotone\Messaging\Channel\PollableChannel\GlobalPollableChannelConfiguration;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;

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
final class DeliveryConfirmationModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $globalPollableChannelConfiguration = ExtensionObjectResolver::resolveUnique(GlobalPollableChannelConfiguration::class, $extensionObjects, GlobalPollableChannelConfiguration::createWithDefaults());
        $errorChannels = [];
        foreach (ExtensionObjectResolver::resolve(PollableChannelConfiguration::class, $extensionObjects) as $pollableChannelConfiguration) {
            $errorChannels[$pollableChannelConfiguration->getChannelName()] = $pollableChannelConfiguration->getErrorChannelName();
        }

        $messagingConfiguration->registerServiceDefinition(
            DeliveryConfirmationInterceptor::class,
            new Definition(DeliveryConfirmationInterceptor::class, [
                new Reference(PendingDeliveryRegistry::class),
                $errorChannels,
                $globalPollableChannelConfiguration->getErrorChannelName(),
                new Reference(ErrorChannelService::class),
                new Reference(ConfiguredMessagingSystem::class),
            ])
        );
        $messagingConfiguration->registerAroundMethodInterceptor(
            AroundInterceptorBuilder::create(
                DeliveryConfirmationInterceptor::class,
                $interfaceToCallRegistry->getFor(DeliveryConfirmationInterceptor::class, 'await'),
                Precedence::DELIVERY_CONFIRMATION_PRECEDENCE,
                CommandBus::class . '||' . AsynchronousRunningEndpoint::class,
            )
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
