<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Flow;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
final class MessageFlowModule implements AnnotationModule
{
    private function __construct(private readonly AnnotationFinder $annotationFinder)
    {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self($annotationRegistrationService);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            return;
        }

        $declared = MessageFlowDiscovery::discover($this->annotationFinder, $interfaceToCallRegistry, $extensionObjects);
        $messagingConfiguration->registerServiceDefinition(MessageFlowGraphBuilder::class, new Definition(MessageFlowGraphBuilder::class, [
            $declared->graph->getDefinition(),
            $declared->collaborators,
            $declared->namedEvents,
            new Definition(HandlerSourceScanner::class),
        ]));
        $messagingConfiguration->registerServiceDefinition(MessageFlowConsoleCommands::class, new Definition(MessageFlowConsoleCommands::class));
        [$messageHandlerBuilder, $consoleCommandConfiguration] = ConsoleCommandModule::prepareConsoleCommandForReference(
            new Reference(MessageFlowConsoleCommands::class),
            new InterfaceToCallReference(MessageFlowConsoleCommands::class, 'dump'),
            'ecotone:flow:dump',
            true,
            $interfaceToCallRegistry,
            'Dumps the message flow graph of the application: commands, events, queries, handlers, aggregates, sagas, projections, gateways and channels, with what each handler emits, as Mermaid or JSON',
        );
        $messagingConfiguration
            ->registerMessageHandler($messageHandlerBuilder)
            ->registerConsoleCommand($consoleCommandConfiguration);
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return [];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
