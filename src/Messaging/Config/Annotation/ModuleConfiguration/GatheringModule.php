<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Collected;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Gathered;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\Splitter;
use Ecotone\Api\Attribute\Transformer;
use Ecotone\Api\ExtensionObject\CombinedMessageChannel;
use Ecotone\Api\ExtensionObject\DelayedMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\GatheringConfiguration;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Gateway\GatheredGroups;
use Ecotone\Api\Messaging\GatheredParts;
use Ecotone\Api\Pulse\PulseConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gathering\CollectedHandlerContext;
use Ecotone\Messaging\Handler\Gathering\GatheredGroupOperations;
use Ecotone\Messaging\Handler\Gathering\GatheredGroupStore;
use Ecotone\Messaging\Handler\Gathering\GatheredGroupStoreRegistration;
use Ecotone\Messaging\Handler\Gathering\GatheredHandlerEndpoints;
use Ecotone\Messaging\Handler\Gathering\GatheringConsoleCommands;
use Ecotone\Messaging\Handler\Gathering\GatheringPartCompletion;
use Ecotone\Messaging\Handler\Gathering\GatheringTimeouts;
use Ecotone\Messaging\Handler\Gathering\InMemoryGatheredGroupStore;
use Ecotone\Messaging\Handler\Gathering\NullPartCompletion;
use Ecotone\Messaging\Handler\Gathering\PartCompletion;
use Ecotone\Messaging\Handler\Gathering\PulseGathering;
use Ecotone\Messaging\Handler\Gathering\PulseGatheringActions;
use Ecotone\Messaging\Handler\Gathering\UngatheredSplitterProcessor;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseRequestBodyBuilder;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseSnapshot;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseSnapshotSectionRegistration;
use Ecotone\Projecting\Config\ProjectionRuns;
use Ecotone\Projecting\ProjectionRun;
use Ecotone\Projecting\ProjectionRunCompletion;

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
 *
 */
final class GatheringModule extends NoExternalConfigurationModule
{
    /**
     * @param list<ProjectionRun> $projectionRuns
     */
    public function __construct(private array $gatherChannelNames, private AnnotationFinder $annotationFinder, private array $collectedEndpointIds, private array $gatheredEndpointIds, private array $projectionRuns)
    {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $gatherChannelNames = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(Splitter::class) as $registration) {
            $splitter = $registration->getAnnotationForMethod();
            if ($splitter->gatherChannelName !== '') {
                $gatherChannelNames[] = $splitter->gatherChannelName;
            }
        }

        $collectedEndpointIds = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(Collected::class) as $registration) {
            foreach ($interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName())->getMethodAnnotations() as $handler) {
                if ($handler instanceof InternalHandler || $handler instanceof CommandHandler || $handler instanceof EventHandler) {
                    $collectedEndpointIds[] = $handler->getEndpointId();
                }
            }
        }
        $gatheredEndpointIds = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(Gathered::class) as $registration) {
            foreach ($interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName())->getMethodAnnotations() as $handler) {
                if (! $handler instanceof InternalHandler && ! $handler instanceof CommandHandler) {
                    continue;
                }
                $channel = $handler->getInputChannelName();
                if ($handler->isEndpointIdGenerated()) {
                    $attribute = $handler instanceof CommandHandler
                        ? '#[CommandHandler(routingKey: ' . var_export($channel, true) . ", endpointId: 'your.stable.id')]"
                        : '#[InternalHandler(inputChannelName: ' . var_export($channel, true) . ", endpointId: 'your.stable.id')]";
                    throw ConfigurationException::create("Gathered handler '{$channel}' has no explicit endpointId. Its groups are stored and listed under that endpointId, so it must stay stable across deploys. Set {$attribute}, or remove #[Gathered] and the splitter's gatherChannelName.");
                }
                $gatheredEndpointIds[$channel] = $handler->getEndpointId();
            }
        }
        $projectionRuns = ProjectionRuns::fromAnnotations($annotationRegistrationService);
        ProjectionRuns::refuseEndpointIdsTakenByHandlers(annotationFinder: $annotationRegistrationService, runs: $projectionRuns);
        foreach ($projectionRuns as $run) {
            $gatheredEndpointIds[$run->gatherChannelName()] = $run->endpointId();
        }
        return new self(gatherChannelNames: $gatherChannelNames, annotationFinder: $annotationRegistrationService, collectedEndpointIds: $collectedEndpointIds, gatheredEndpointIds: $gatheredEndpointIds, projectionRuns: $projectionRuns);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $this->assertLicence($messagingConfiguration);
        $this->validateWiring($interfaceToCallRegistry);
        $this->validateTimeouts($extensionObjects, $interfaceToCallRegistry);
        $this->refuseStaticallyUngatheredSplitters();
        $this->registerPartCompletion($messagingConfiguration);
        $this->registerStore(messagingConfiguration: $messagingConfiguration, extensionObjects: $extensionObjects, interfaceToCallRegistry: $interfaceToCallRegistry);
        $this->refuseUnregisteredProjectionRunChannels(messagingConfiguration: $messagingConfiguration, extensionObjects: $extensionObjects);
        $this->registerProjectionRunCompletions($messagingConfiguration);
        $this->registerPulse(configuration: $messagingConfiguration, extensionObjects: $extensionObjects, interfaceToCallRegistry: $interfaceToCallRegistry);
    }

    private function assertLicence(Configuration $messagingConfiguration): void
    {
        if ($this->gatherChannelNames !== [] && ! $messagingConfiguration->isRunningForEnterpriseLicence()) {
            $gatherChannelName = $this->gatherChannelNames[0];
            throw LicensingException::create("Splitter gather channel '{$gatherChannelName}' requires an Ecotone Enterprise licence. Provide your licence key as `licenceKey` in the Symfony, Laravel or Tempest Ecotone configuration, or as the `licenceKey:` argument of EcotoneLite, or start a trial at https://ecotone.tech/pricing. Alternatively, remove gatherChannelName from #[Splitter].");
        }
    }

    private function validateWiring(InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->annotationFinder->findAnnotatedMethods(Splitter::class) as $registration) {
            $splitter = $registration->getAnnotationForMethod();
            if ($splitter->gatherChannelName !== '' && $splitter->isEndpointIdGenerated()) {
                $channel = $splitter->getInputChannelName();
                throw ConfigurationException::create("Splitter '{$channel}' names gather channel '{$splitter->gatherChannelName}' but has no explicit endpointId. Gathering needs a stable splitter identity across retries and restarts. Add endpointId: '{$channel}.split' to #[Splitter], or remove gatherChannelName.");
            }
        }
        $gatheredChannels = [];
        foreach ($this->annotationFinder->findAnnotatedMethods(Gathered::class) as $registration) {
            $interface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
            if (array_filter($interface->getMethodAnnotations(), static fn (object $annotation): bool => $annotation instanceof InternalHandler || $annotation instanceof CommandHandler) === []) {
                $method = $registration->getMethodName();
                throw ConfigurationException::create("Method '{$method}' is marked #[Gathered] but has no ordinary message handler attribute. Stack it on #[InternalHandler] or #[CommandHandler], or remove #[Gathered].");
            }
            foreach ($interface->getMethodAnnotations() as $annotation) {
                if ($annotation instanceof InternalHandler || $annotation instanceof CommandHandler) {
                    $gatheredChannels[] = $annotation->getInputChannelName();
                    if (! $interface->hasFirstParameter() || ! $interface->getFirstParameter()->getTypeDescriptor()->equals(Type::create(GatheredParts::class))) {
                        $channel = $annotation->getInputChannelName();
                        throw ConfigurationException::create("Gathered handler '{$channel}' must receive Ecotone\\Api\\Messaging\\GatheredParts as its first payload parameter. Change that parameter type to GatheredParts, or remove #[Gathered] and the splitter's gatherChannelName.");
                    }
                }
                if (($annotation instanceof InternalHandler || $annotation instanceof CommandHandler) && ! in_array($annotation->getInputChannelName(), $this->gatherChannelNames, true)) {
                    $channel = $annotation->getInputChannelName();
                    throw ConfigurationException::create("Gathered handler '{$channel}' has no splitter pointing to it. Set gatherChannelName: '{$channel}' on a #[Splitter], or remove #[Gathered] from this handler.");
                }
                if (($annotation instanceof InternalHandler || $annotation instanceof CommandHandler) && ! $interface->hasAnnotation(Asynchronous::class)) {
                    $channel = $annotation->getInputChannelName();
                    throw ConfigurationException::create("Gathered handler '{$channel}' requires an asynchronous channel. Add #[Asynchronous('channelName')] to the handler, or remove #[Gathered] and the splitter's gatherChannelName.");
                }
            }
        }
        foreach ($this->gatherChannelNames as $channel) {
            if (! in_array($channel, $gatheredChannels, true)) {
                throw ConfigurationException::create("Splitter gather channel '{$channel}' must target a gathered handler. Define an #[InternalHandler] or #[CommandHandler] on '{$channel}' with #[Gathered] and #[Asynchronous], or remove gatherChannelName from #[Splitter].");
            }
        }
    }

    private function validateTimeouts(array $extensionObjects, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $channels = [];
        $combinedChannels = [];
        foreach ($extensionObjects as $extension) {
            if ($extension instanceof MessageChannelBuilder) {
                $channels[$extension->getMessageChannelName()] = $extension;
            }
            if ($extension instanceof CombinedMessageChannel) {
                $combinedChannels[$extension->getReferenceName()] = $extension->combinedChannels;
            }
        }
        foreach (array_merge($this->annotationFinder->findAnnotatedMethods(Gathered::class), $this->annotationFinder->findAnnotatedMethods(Collected::class)) as $registration) {
            $marker = $registration->getAnnotationForMethod();
            $collecting = $marker instanceof Collected;
            $timeout = $collecting ? $marker->maxWait : $marker->timeout;
            $kind = $collecting ? 'Collected' : 'Gathered';
            $setting = $collecting ? 'maxWait' : 'timeout';
            $hasTimeout = $collecting ? 'has maxWait' : 'has a timeout';
            if ($timeout === null) {
                continue;
            }
            $interface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
            $asynchronous = $interface->getSingleAnnotation(Type::create(Asynchronous::class));
            $handler = array_values(array_filter($interface->getMethodAnnotations(), static fn (object $annotation): bool => $annotation instanceof InternalHandler || $annotation instanceof CommandHandler || $annotation instanceof EventHandler))[0];
            $handlerChannel = $handler instanceof EventHandler ? $handler->listenTo : $handler->getInputChannelName();
            $handlerDescription = $collecting ? (new CollectedHandlerContext(className: $registration->getClassName(), methodName: $registration->getMethodName(), endpointId: $handler->getEndpointId(), groupByDescription: ''))->description : "'{$handlerChannel}'";
            $channelName = $asynchronous->getChannelName()[0];
            $channelName = $combinedChannels[$channelName][0] ?? $channelName;
            $builder = $channels[$channelName] ?? null;
            if (! $builder instanceof DelayedMessageChannelBuilder || ! $builder->supportsDelayedMessages()) {
                $handlerChannel = $handler instanceof EventHandler ? $handler->listenTo : $handler->getInputChannelName();
                throw ConfigurationException::create("{$kind} handler {$handlerDescription} {$hasTimeout} but asynchronous channel '{$channelName}' cannot hold delayed messages. Use a channel builder implementing DelayedMessageChannelBuilder with delay support, enable delays on this channel, or remove {$setting} from #[{$kind}].");
            }
            $maximumDelay = $builder->maximumDelay();
            if ($maximumDelay !== null && $timeout->toMilliseconds() > $maximumDelay->toMilliseconds()) {
                $handlerChannel = $handler instanceof EventHandler ? $handler->listenTo : $handler->getInputChannelName();
                $timeoutMilliseconds = $timeout->toMilliseconds();
                $maximumMilliseconds = $maximumDelay->toMilliseconds();
                throw ConfigurationException::create("{$kind} handler {$handlerDescription} {$setting} of {$timeoutMilliseconds} milliseconds exceeds asynchronous channel '{$channelName}' maximum delay of {$maximumMilliseconds} milliseconds. Reduce {$setting} in #[{$kind}], use a channel with a longer maximum delay, or remove {$setting}.");
            }
        }
    }

    private function registerPartCompletion(Configuration $messagingConfiguration): void
    {
        $messagingConfiguration->registerServiceDefinition(NullPartCompletion::class, new Definition(NullPartCompletion::class));
        $messagingConfiguration->registerServiceDefinition(GatheringPartCompletion::class, new Definition(GatheringPartCompletion::class, [new Reference(ChannelResolver::class), new Reference(MessageBuilder::class)]));
        $messagingConfiguration->registerServiceDefinition(PartCompletion::class, LicenceDecider::prepareDefinition(className: PartCompletion::class, openCoreServiceReference: NullPartCompletion::class, enterpriseServiceReference: GatheringPartCompletion::class));
    }

    private function registerStore(Configuration $messagingConfiguration, array $extensionObjects, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $projectionRuns = $this->licensedProjectionRuns($messagingConfiguration);
        if ($this->gatherChannelNames !== [] || $projectionRuns !== [] || $this->annotationFinder->findAnnotatedMethods(Collected::class) !== []) {
            $configuration = ExtensionObjectResolver::resolveUnique(className: GatheringConfiguration::class, extensionObjects: $extensionObjects, default: GatheringConfiguration::createWithDefaults());
            $timeouts = [];
            foreach ($this->annotationFinder->findAnnotatedMethods(Gathered::class) as $registration) {
                $gathered = $registration->getAnnotationForMethod();
                $interface = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName())->getMethodAnnotations();
                foreach ($interface as $annotation) {
                    if ($annotation instanceof InternalHandler || $annotation instanceof CommandHandler) {
                        $timeouts[$annotation->getInputChannelName()] = $gathered->timeout?->toMilliseconds();
                    }
                }
            }
            $messagingConfiguration->registerServiceDefinition(GatheringTimeouts::class, new Definition(GatheringTimeouts::class, [$timeouts]));
            $messagingConfiguration->registerServiceDefinition(GatheredHandlerEndpoints::class, new Definition(GatheredHandlerEndpoints::class, [$this->gatheredEndpointIds]));
            $store = ExtensionObjectResolver::resolveUnique(className: GatheredGroupStoreRegistration::class, extensionObjects: $extensionObjects, default: new GatheredGroupStoreRegistration(InMemoryGatheredGroupStore::class));
            if ($store->serviceReference === InMemoryGatheredGroupStore::class && ! $messagingConfiguration->isRunningForTest()) {
                if ($this->collectedEndpointIds !== []) {
                    $registration = $this->annotationFinder->findAnnotatedMethods(Collected::class)[0];
                    $handlerDescription = (new CollectedHandlerContext(className: $registration->getClassName(), methodName: $registration->getMethodName(), endpointId: $this->collectedEndpointIds[0], groupByDescription: ''))->description;
                    $scope = $this->gatherChannelNames === [] ? "Collected handler {$handlerDescription} requires" : "Gather channel '{$this->gatherChannelNames[0]}' and collected handler {$handlerDescription} require";
                    $entities = $this->gatherChannelNames === [] ? 'batches' : 'groups and batches';
                    $removal = $this->gatherChannelNames === [] ? '#[Collected]' : 'gatherChannelName from #[Splitter] and #[Collected] from the service handler';
                    throw ConfigurationException::create("{$scope} durable storage outside flow testing: {$entities} must survive restarts and be shared between consumers. Install ecotone/dbal and enable its module: add ModulePackageList::DBAL_PACKAGE to EcotoneLite::bootstrap(..., enabledModules: [...]), or 'dbal' to enabledModulePackageNames (the Symfony bundle option, config/ecotone.php key in Laravel, or EcotoneConfig argument in Tempest). Before the first message, run ecotone:migration:database:setup --initialize --feature=gathering, or generate SQL for your migrations with ecotone:migration:database:setup --sql --feature=gathering. Use EcotoneLite::bootstrapFlowTesting() for in-memory flow tests, or remove {$removal}.");
                }
                if ($this->gatherChannelNames === []) {
                    $run = $projectionRuns[0];
                    throw ConfigurationException::create("Projection '{$run->projectionName}' runs its {$run->operation} asynchronously on '{$run->asyncChannelName}', so the run is tracked as a gathering group that must survive restarts and be shared between consumers, which needs durable storage when bootstrapping without test configuration. Install ecotone/dbal and enable its module: add ModulePackageList::DBAL_PACKAGE to EcotoneLite::bootstrap(..., enabledModules: [...]), or 'dbal' to enabledModulePackageNames (the Symfony bundle option, config/ecotone.php key in Laravel, or EcotoneConfig argument in Tempest). Before the first {$run->operation}, run ecotone:migration:database:setup --initialize --feature=gathering, or generate SQL for your migrations with ecotone:migration:database:setup --sql --feature=gathering. Use EcotoneLite::bootstrapFlowTesting() for in-memory flow tests, or remove asyncChannelName from {$run->attributeName()} to run the {$run->operation} in the calling process.");
                }
                $gatherChannelName = $this->gatherChannelNames[0];
                throw ConfigurationException::create("Gather channel '{$gatherChannelName}' requires durable storage when bootstrapping without test configuration: in-memory groups cannot survive restarts or be shared between workers. Install ecotone/dbal and enable its module: add ModulePackageList::DBAL_PACKAGE to EcotoneLite::bootstrap(..., enabledModules: [...]), or 'dbal' to enabledModulePackageNames (the Symfony bundle option, config/ecotone.php key in Laravel, or EcotoneConfig argument in Tempest). Before the first message, run ecotone:migration:database:setup --initialize --feature=gathering, or generate SQL for your migrations with ecotone:migration:database:setup --sql --feature=gathering. Use EcotoneLite::bootstrapFlowTesting() for in-memory flow tests, or remove gatherChannelName from #[Splitter].");
            }
            $messagingConfiguration->registerServiceDefinition(InMemoryGatheredGroupStore::class, new Definition(InMemoryGatheredGroupStore::class, [new Reference(EcotoneClockInterface::class), new Reference(MessageBuilder::class)]));
            $messagingConfiguration->registerServiceDefinition(GatheredGroupStore::class, new Reference($store->serviceReference));
            $messagingConfiguration->registerServiceDefinition(GatheredGroups::class, new Definition(GatheredGroupOperations::class, [new Reference(GatheredGroupStore::class), new Reference(EcotoneClockInterface::class), $configuration->retention->toMilliseconds(), new Reference(ChannelResolver::class), new Reference(MessageBuilder::class), $this->gatheredEndpointIds, array_values(array_unique($this->collectedEndpointIds))]));
            $messagingConfiguration
                ->registerMessageHandler(MessageProcessorActivatorBuilder::createForMethod(GatheredGroups::class, 'cleanupClosedGroups')->withInputChannelName('ecotone.gathering.cleanup'))
                ->registerConsoleCommand(ConsoleCommandConfiguration::create(channelName: 'ecotone.gathering.cleanup', name: 'ecotone:gathering:cleanup', parameterNames: [], description: 'Removes closed gathering groups after their retention period'));
            $this->registerConsoleCommands($messagingConfiguration, $interfaceToCallRegistry);
        }
    }

    /**
     * @param object[] $extensionObjects
     */
    private function refuseUnregisteredProjectionRunChannels(Configuration $messagingConfiguration, array $extensionObjects): void
    {
        if ($this->licensedProjectionRuns($messagingConfiguration) === []) {
            return;
        }
        $registered = [];
        foreach ($extensionObjects as $extension) {
            if ($extension instanceof MessageChannelBuilder) {
                $registered[$extension->getMessageChannelName()] = true;
            }
            if ($extension instanceof CombinedMessageChannel) {
                $registered[$extension->getReferenceName()] = true;
            }
        }
        foreach ($this->licensedProjectionRuns($messagingConfiguration) as $run) {
            if (! isset($registered[$run->asyncChannelName])) {
                throw ConfigurationException::create("Projection '{$run->projectionName}' runs its {$run->operation} on '{$run->asyncChannelName}' (asyncChannelName of {$run->attributeName()}), which is not registered as a message channel. Register it from a #[ServiceContext] method, for example DbalBackedMessageChannelBuilder::create('{$run->asyncChannelName}'), or remove asyncChannelName from {$run->attributeName()} to run the {$run->operation} in the calling process.");
            }
            if (! isset($registered[$run->batchCompletionChannelName])) {
                throw ConfigurationException::create("Projection '{$run->projectionName}' consumes the batch reports of its {$run->operation} on '{$run->batchCompletionChannelName}' (batchCompletionChannelName of {$run->attributeName()}), which is not registered as a message channel. Register it from a #[ServiceContext] method, for example DbalBackedMessageChannelBuilder::create('{$run->batchCompletionChannelName}'), or remove batchCompletionChannelName to consume them on '{$run->asyncChannelName}'.");
            }
        }
    }

    /**
     * @return list<ProjectionRun>
     */
    private function licensedProjectionRuns(Configuration $messagingConfiguration): array
    {
        return $messagingConfiguration->isRunningForEnterpriseLicence() ? $this->projectionRuns : [];
    }

    private function registerProjectionRunCompletions(Configuration $messagingConfiguration): void
    {
        if ($this->licensedProjectionRuns($messagingConfiguration) === []) {
            return;
        }
        $messagingConfiguration->registerServiceDefinition(ProjectionRunCompletion::class, new Definition(ProjectionRunCompletion::class));
        foreach ($this->licensedProjectionRuns($messagingConfiguration) as $run) {
            $messagingConfiguration
                ->registerMessageHandler(
                    MessageProcessorActivatorBuilder::create()
                        ->chainInterceptedProcessor(MethodInvokerBuilder::create(ProjectionRunCompletion::class, InterfaceToCallReference::create(ProjectionRunCompletion::class, 'complete'), [PayloadBuilder::create('parts')]))
                        ->withEndpointId($run->endpointId())
                        ->withInputChannelName($run->gatherChannelName())
                )
                ->registerAsynchronousEndpoint($run->batchCompletionChannelName, $run->endpointId());
        }
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return ($this->gatherChannelNames !== [] || $this->collectedEndpointIds !== [] || $this->projectionRuns !== []) && ExtensionObjectResolver::resolve(PulseConfiguration::class, $serviceExtensions) !== []
            ? [new PulseSnapshotSectionRegistration(sectionName: 'gathering', serviceReference: PulseGathering::class, tenantScoped: true)]
            : [];
    }

    private function registerPulse(Configuration $configuration, array $extensionObjects, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (($this->gatherChannelNames === [] && $this->collectedEndpointIds === [] && $this->projectionRuns === []) || ExtensionObjectResolver::resolve(PulseConfiguration::class, $extensionObjects) === []) {
            return;
        }
        $configuration->registerServiceDefinition(PulseGathering::class, new Definition(PulseGathering::class, [new Reference(GatheredGroups::class), array_values(array_unique([...array_values($this->gatheredEndpointIds), ...$this->collectedEndpointIds]))]));
        $configuration->registerServiceDefinition(PulseGatheringActions::class, new Definition(PulseGatheringActions::class, [new Reference(GatheredGroups::class)]));
        foreach (['requestRelease' => 'release', 'discard' => 'discard'] as $method => $action) {
            $configuration->registerMessageHandler(
                MessageProcessorActivatorBuilder::createForMethod(PulseGatheringActions::class, $interfaceToCallRegistry->getFor(PulseGatheringActions::class, $method))
                    ->withInputChannelName('ecotone.pulse.gathering.' . $action)
                    ->withMethodParameterConverters([
                        PulseRequestBodyBuilder::create('payload'),
                        HeaderBuilder::createOptional('sourceService', DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME),
                        AllHeadersBuilder::createWith('headers'),
                        ReferenceBuilder::create('snapshot', PulseSnapshot::class),
                    ])
            );
        }
    }

    private function registerConsoleCommands(Configuration $configuration, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $configuration->registerServiceDefinition(GatheringConsoleCommands::class, new Definition(GatheringConsoleCommands::class, [new Reference(GatheredGroups::class)]));
        foreach ([
            'listGroups' => ['list', 'Lists gathered groups and collected batches by endpointId'],
            'requestRelease' => ['release', 'Requests release on the gathered handler asynchronous channel'],
            'discard' => ['discard', 'Discards a group without invoking its gathered handler'],
        ] as $method => [$commandName, $description]) {
            [$handler, $command] = ConsoleCommandModule::prepareConsoleCommandForReference(
                reference: new Reference(GatheringConsoleCommands::class),
                interfaceToCallReference: new InterfaceToCallReference(GatheringConsoleCommands::class, $method),
                commandName: 'ecotone:gathering:' . $commandName,
                interfaceToCallRegistry: $interfaceToCallRegistry,
                description: $description,
            );
            $configuration->registerMessageHandler($handler)->registerConsoleCommand($command);
        }
    }

    private function refuseStaticallyUngatheredSplitters(): void
    {
        if ($this->gatherChannelNames === []) {
            return;
        }
        $outputs = [];
        $splitters = [];
        foreach ([Splitter::class, InternalHandler::class, CommandHandler::class, Transformer::class] as $attribute) {
            foreach ($this->annotationFinder->findAnnotatedMethods($attribute) as $registration) {
                $endpoint = $registration->getAnnotationForMethod();
                $outputs[$endpoint->getInputChannelName()][] = $endpoint->getOutputChannelName();
                if ($endpoint instanceof Splitter) {
                    $splitters[$endpoint->getInputChannelName()][] = $endpoint;
                }
            }
        }
        foreach ($this->annotationFinder->findAnnotatedMethods(Splitter::class) as $registration) {
            $splitter = $registration->getAnnotationForMethod();
            if ($splitter->gatherChannelName === '') {
                continue;
            }
            $pending = [$splitter->getOutputChannelName()];
            $visited = [];
            while ($pending !== []) {
                $channel = array_pop($pending);
                if ($channel === '' || isset($visited[$channel])) {
                    continue;
                }
                $visited[$channel] = true;
                foreach ($splitters[$channel] ?? [] as $nestedSplitter) {
                    if ($nestedSplitter->gatherChannelName === '') {
                        throw UngatheredSplitterProcessor::refusal($nestedSplitter->getEndpointId());
                    }
                }
                $pending = [...$pending, ...($outputs[$channel] ?? [])];
            }
        }
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
