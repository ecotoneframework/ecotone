<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Manager;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
/**
 * Module that collects ChannelManager extension objects and registers channel setup commands.
 *
 * licence Apache-2.0
 */
class ChannelSetupModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $channelManagerReferences = ExtensionObjectResolver::resolve(ChannelManagerReference::class, $extensionObjects);

        $channelManagerRefs = array_map(
            fn (ChannelManagerReference $ref) => new Reference($ref->getReferenceName()),
            $channelManagerReferences
        );

        // Collect all pollable channels from extension objects
        $messageChannelBuilders = ExtensionObjectResolver::resolve(\Ecotone\Messaging\Channel\MessageChannelBuilder::class, $extensionObjects);
        $allPollableChannelNames = array_map(
            fn (\Ecotone\Messaging\Channel\MessageChannelBuilder $builder) => $builder->getMessageChannelName(),
            array_filter(
                $messageChannelBuilders,
                fn (\Ecotone\Messaging\Channel\MessageChannelBuilder $builder) => $builder->isPollable()
            )
        );

        $messagingConfiguration->registerServiceDefinition(
            ChannelSetupManager::class,
            new Definition(ChannelSetupManager::class, [$channelManagerRefs, $allPollableChannelNames])
        );

        $messagingConfiguration->registerServiceDefinition(
            ChannelSetupCommand::class,
            new Definition(ChannelSetupCommand::class, [new Reference(ChannelSetupManager::class)])
        );

        $messagingConfiguration->registerServiceDefinition(
            ChannelDeleteCommand::class,
            new Definition(ChannelDeleteCommand::class, [new Reference(ChannelSetupManager::class)])
        );

        // Register console commands
        $this->registerConsoleCommand('setup', 'ecotone:migration:channel:setup', ChannelSetupCommand::class, $messagingConfiguration, $interfaceToCallRegistry);
        $this->registerConsoleCommand('delete', 'ecotone:migration:channel:delete', ChannelDeleteCommand::class, $messagingConfiguration, $interfaceToCallRegistry);
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof ChannelManagerReference
            || $extensionObject instanceof ChannelInitializationConfiguration
            || $extensionObject instanceof \Ecotone\Messaging\Channel\MessageChannelBuilder;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    private function registerConsoleCommand(
        string $methodName,
        string $commandName,
        string $className,
        Configuration $configuration,
        InterfaceToCallRegistry $interfaceToCallRegistry
    ): void {
        [$messageHandlerBuilder, $oneTimeCommandConfiguration] = ConsoleCommandModule::prepareConsoleCommandForReference(
            new Reference($className),
            new InterfaceToCallReference($className, $methodName),
            $commandName,
            true,
            $interfaceToCallRegistry
        );

        $configuration
            ->registerMessageHandler($messageHandlerBuilder)
            ->registerConsoleCommand($oneTimeCommandConfiguration);
    }
}
