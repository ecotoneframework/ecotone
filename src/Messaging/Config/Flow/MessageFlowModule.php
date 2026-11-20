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
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\Flow\FlowVersionsConfiguration;
use Ecotone\Api\Pulse\PulseConfiguration;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\Flow\Version\FlowVersionConsoleCommands;
use Ecotone\Messaging\Config\Flow\Version\FlowVersions;
use Ecotone\Messaging\Config\Flow\Version\FlowVersionsNotConfigured;
use Ecotone\Messaging\Config\Flow\Version\PulseFlowVersions;
use Ecotone\Messaging\Config\Flow\Version\PulseFlowVersionsNotConfigured;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseRequest;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseRequestBodyBuilder;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseSnapshot;

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
        $flowVersionsConfigurations = ExtensionObjectResolver::resolve(FlowVersionsConfiguration::class, $extensionObjects);
        if (! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            if ($flowVersionsConfigurations !== []) {
                throw LicensingException::create('Flow versions are available only as part of Ecotone Enterprise. Provide a licence key, or remove the FlowVersionsConfiguration from your #[ServiceContext]. See https://docs.ecotone.tech/enterprise for details.');
            }

            return;
        }

        $declared = MessageFlowDiscovery::discover($this->annotationFinder, $interfaceToCallRegistry, $extensionObjects);
        $distributedBusParameters = self::distributedBusParameters($interfaceToCallRegistry);
        $messagingConfiguration->registerServiceDefinition(MessageFlowGraphBuilder::class, new Definition(MessageFlowGraphBuilder::class, [
            $declared->graph->getDefinition(),
            $declared->collaborators,
            $declared->namedEvents,
            $distributedBusParameters,
            new Definition(HandlerSourceScanner::class),
        ]));
        $messagingConfiguration->registerServiceDefinition(MessageFlowConsoleCommands::class, new Definition(MessageFlowConsoleCommands::class));
        [$messageHandlerBuilder, $consoleCommandConfiguration] = ConsoleCommandModule::prepareConsoleCommandForReference(
            new Reference(MessageFlowConsoleCommands::class),
            new InterfaceToCallReference(MessageFlowConsoleCommands::class, 'dump'),
            'ecotone:flow:dump',
            $interfaceToCallRegistry,
            'Dumps the message flow graph of the application: commands, events, queries, handlers, aggregates, sagas, projections, gateways and channels, with what each handler emits, as Mermaid or JSON',
        );
        $messagingConfiguration
            ->registerMessageHandler($messageHandlerBuilder)
            ->registerConsoleCommand($consoleCommandConfiguration);
        $this->registerFlowVersions($messagingConfiguration, $flowVersionsConfigurations, $interfaceToCallRegistry);
        if (ExtensionObjectResolver::resolve(PulseConfiguration::class, $extensionObjects) !== []) {
            $this->registerPulseFlowVersions(messagingConfiguration: $messagingConfiguration, hasLocation: $flowVersionsConfigurations !== [], serviceName: ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults())->serviceName, interfaceToCallRegistry: $interfaceToCallRegistry);
        }
    }

    private function registerPulseFlowVersions(Configuration $messagingConfiguration, bool $hasLocation, string $serviceName, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $responder = $hasLocation ? PulseFlowVersions::class : PulseFlowVersionsNotConfigured::class;
        $messagingConfiguration->registerServiceDefinition($responder, new Definition($responder, $hasLocation ? [new Reference(FlowVersions::class), $serviceName] : [$serviceName]));
        $sourceService = HeaderBuilder::createOptional('sourceService', DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME);
        $queryId = HeaderBuilder::createOptional('queryId', PulseRequest::QUERY_ID_HEADER);
        $snapshot = ReferenceBuilder::create('snapshot', PulseSnapshot::class);
        $messagingConfiguration->registerMessageHandler(
            MessageProcessorActivatorBuilder::createForMethod($responder, $interfaceToCallRegistry->getFor($responder, 'list'))
                ->withInputChannelName(PulseFlowVersions::LIST_ROUTING_KEY)
                ->withMethodParameterConverters([$sourceService, $queryId, $snapshot])
        );
        $messagingConfiguration->registerMessageHandler(
            MessageProcessorActivatorBuilder::createForMethod($responder, $interfaceToCallRegistry->getFor($responder, 'version'))
                ->withInputChannelName(PulseFlowVersions::VERSION_ROUTING_KEY)
                ->withMethodParameterConverters([PulseRequestBodyBuilder::create('payload'), $sourceService, $queryId, $snapshot])
        );
    }

    /**
     * @param list<FlowVersionsConfiguration> $flowVersionsConfigurations
     */
    private function registerFlowVersions(Configuration $messagingConfiguration, array $flowVersionsConfigurations, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (count($flowVersionsConfigurations) > 1) {
            throw ConfigurationException::create('FlowVersionsConfiguration was registered ' . count($flowVersionsConfigurations) . ' times, but an application keeps its flow versions in exactly one location. Keep a single #[ServiceContext] returning FlowVersionsConfiguration::createWithLocation(...) and remove the others.');
        }
        $commands = FlowVersionsNotConfigured::class;
        if ($flowVersionsConfigurations !== []) {
            $location = $flowVersionsConfigurations[0]->location;
            if (preg_match('#^[A-Za-z][A-Za-z0-9+.-]+://#', $location) === 1) {
                throw ConfigurationException::create("FlowVersionsConfiguration points at location `{$location}`, which is a stream address, but flow versions are kept in a local directory that ships with the application. Pass an absolute path, for example FlowVersionsConfiguration::createWithLocation(__DIR__ . '/../flow-versions').");
            }
            if (preg_match('#^(/|[A-Za-z]:[\\\\/])#', $location) !== 1) {
                throw ConfigurationException::create("FlowVersionsConfiguration points at location `{$location}`, which is relative, so it would resolve differently for a console command and a web request. Pass an absolute path, for example FlowVersionsConfiguration::createWithLocation(__DIR__ . '/../flow-versions').");
            }
            $messagingConfiguration->registerServiceDefinition(FlowVersions::class, new Definition(FlowVersions::class, [self::withoutParentSegments($flowVersionsConfigurations[0]->location), new Reference(EcotoneClockInterface::class)]));
            $commands = FlowVersionConsoleCommands::class;
        }
        $messagingConfiguration->registerServiceDefinition($commands, new Definition($commands));
        foreach ([
            'store' => ['ecotone:flow:store', 'Stores the current message flow graph as a flow version under the given label, in the location of FlowVersionsConfiguration; --replace stores it again under a label that is already stored'],
            'versions' => ['ecotone:flow:versions', 'Lists the stored flow versions, from the earliest stored to the latest'],
            'compare' => ['ecotone:flow:compare', 'Compares a stored flow version (the one stored most recently by default, or --from) with the current code (or with the stored version --to), and reports added, removed and changed parts of the message flow as text, JSON or Mermaid'],
        ] as $method => [$commandName, $description]) {
            [$messageHandlerBuilder, $consoleCommandConfiguration] = ConsoleCommandModule::prepareConsoleCommandForReference(
                reference: new Reference($commands),
                interfaceToCallReference: new InterfaceToCallReference($commands, $method),
                commandName: $commandName,
                interfaceToCallRegistry: $interfaceToCallRegistry,
                description: $description,
            );
            $messagingConfiguration
                ->registerMessageHandler($messageHandlerBuilder)
                ->registerConsoleCommand($consoleCommandConfiguration);
        }
    }

    private static function withoutParentSegments(string $location): string
    {
        preg_match('#^(/|[A-Za-z]:[\\\\/])(.*)$#', $location, $parts);
        $segments = [];
        foreach (preg_split('#[\\\\/]+#', $parts[2], flags: PREG_SPLIT_NO_EMPTY) as $segment) {
            if ($segment === '..') {
                array_pop($segments);
            } elseif ($segment !== '.') {
                $segments[] = $segment;
            }
        }

        return $parts[1] . implode('/', $segments);
    }

    /**
     * @return array<string, list<string>>
     */
    private static function distributedBusParameters(InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $parameters = [];
        foreach (['sendCommand', 'convertAndSendCommand', 'publishEvent', 'convertAndPublishEvent', 'sendMessage'] as $method) {
            foreach ($interfaceToCallRegistry->getFor(DistributedBus::class, $method)->getInterfaceParameters() as $parameter) {
                $parameters[$method][] = $parameter->getName();
            }
        }

        return $parameters;
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
