<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\Collector\CollectorSenderInterceptor;
use Ecotone\Messaging\Channel\Collector\CollectorStorage;
use Ecotone\Messaging\Channel\DynamicChannel\DynamicMessageChannelBuilder;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\PollableChannel\GlobalPollableChannelConfiguration;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Precedence;
use Ecotone\Modelling\CommandBus;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class CollectorModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const ECOTONE_COLLECTOR_DEFAULT_PROXY = 'ecotone.collector.default_proxy';

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $globalPollableChannelConfiguration = ExtensionObjectResolver::resolveUnique(GlobalPollableChannelConfiguration::class, $extensionObjects, GlobalPollableChannelConfiguration::createWithDefaults());
        $pollableMessageChannels = ExtensionObjectResolver::resolve(MessageChannelBuilder::class, $extensionObjects);
        $pollableChannelConfigurations = ExtensionObjectResolver::resolve(PollableChannelConfiguration::class, $extensionObjects);

        $takenChannelNames = [];
        foreach ($pollableChannelConfigurations as $pollableChannelConfiguration) {
            if (in_array($pollableChannelConfiguration->getChannelName(), $takenChannelNames)) {
                throw ConfigurationException::create("Channel {$pollableChannelConfiguration->getChannelName()} is already taken by another collector");
            }

            $takenChannelNames[] = $pollableChannelConfiguration->getChannelName();
        }


        foreach ($pollableMessageChannels as $pollableMessageChannel) {
            $channelConfiguration = $globalPollableChannelConfiguration;

            foreach ($pollableChannelConfigurations as $pollableChannelConfiguration) {
                if ($pollableChannelConfiguration->getChannelName() === $pollableMessageChannel->getMessageChannelName()) {
                    $channelConfiguration = $pollableChannelConfiguration;
                }
            }

            if (! $channelConfiguration->isCollectorEnabled()) {
                continue;
            }

            $collectorReference = Reference::to('polling.'.$pollableMessageChannel->getMessageChannelName().'.collector_storage');
            $messagingConfiguration->registerServiceDefinition($collectorReference, new Definition(CollectorStorage::class));
            $messagingConfiguration->registerChannelInterceptor(
                new CollectorChannelInterceptorBuilder($pollableMessageChannel->getMessageChannelName(), $collectorReference),
            );

            $collectorSenderInterceptorReference = CollectorSenderInterceptor::class . '.' . $pollableMessageChannel->getMessageChannelName();
            $messagingConfiguration->registerServiceDefinition(
                $collectorSenderInterceptorReference,
                new Definition(
                    CollectorSenderInterceptor::class,
                    [
                        $collectorReference,
                        $pollableMessageChannel->getMessageChannelName(),
                    ]
                )
            );
            $collectorSenderInterceptorInterfaceToCall = $interfaceToCallRegistry->getFor(CollectorSenderInterceptor::class, 'send');
            $messagingConfiguration->registerAroundMethodInterceptor(
                AroundInterceptorBuilder::create(
                    $collectorSenderInterceptorReference,
                    $collectorSenderInterceptorInterfaceToCall,
                    Precedence::COLLECTOR_SENDER_PRECEDENCE,
                    CommandBus::class . '||' . AsynchronousRunningEndpoint::class
                )
            );
        }
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof PollableChannelConfiguration
            || $extensionObject instanceof GlobalPollableChannelConfiguration
            /** Dynamic and RoundRobin are proxies, therefore should not be intercepted */
            || ($extensionObject instanceof MessageChannelBuilder && $extensionObject->isPollable() && ! ($extensionObject instanceof DynamicMessageChannelBuilder));
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
