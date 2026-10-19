<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class MessagingCommandsModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const ECOTONE_EXECUTE_CONSOLE_COMMAND_EXECUTOR = 'ecotone.consoleCommand.execute';
    public const ECOTONE_CONSOLE_COMMAND_NAME = 'ecotone_console_command_name';

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $messagingConfiguration->registerServiceDefinition(MessagingBaseCommand::class, new Definition(MessagingBaseCommand::class));
        $messagingConfiguration->registerMessageHandler(
            MessageProcessorActivatorBuilder::createForMethod(MessagingBaseCommand::class, new InterfaceToCallReference(MessagingBaseCommand::class, 'executeConsoleCommand'))
                ->withMethodParameterConverters([
                    HeaderBuilder::create('commandName', self::ECOTONE_CONSOLE_COMMAND_NAME),
                    PayloadBuilder::create('parameters'),
                ])
                ->withInputChannelName(self::ECOTONE_EXECUTE_CONSOLE_COMMAND_EXECUTOR)
        );

        $this->registerConsoleCommand('runAsynchronousEndpointCommand', 'ecotone:run', $messagingConfiguration, $interfaceToCallRegistry, 'Runs an asynchronous message consumer by name');
        $this->registerConsoleCommand('listAsynchronousEndpointsCommand', 'ecotone:list', $messagingConfiguration, $interfaceToCallRegistry, 'Lists all registered asynchronous message consumers');
    }

    private function registerConsoleCommand(string $methodName, string $commandName, Configuration $configuration, InterfaceToCallRegistry $interfaceToCallRegistry, string $description = ''): void
    {
        [$messageHandlerBuilder, $oneTimeCommandConfiguration] = ConsoleCommandModule::prepareConsoleCommandForReference(
            new Reference(MessagingBaseCommand::class),
            new InterfaceToCallReference(MessagingBaseCommand::class, $methodName),
            $commandName,
            false,
            $interfaceToCallRegistry,
            $description
        );
        $configuration
            ->registerMessageHandler($messageHandlerBuilder)
            ->registerConsoleCommand($oneTimeCommandConfiguration);
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
