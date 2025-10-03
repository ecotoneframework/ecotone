<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\StreamBasedSource;
use Ecotone\Messaging\Channel\CombinedMessageChannel;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\Config\Routing\RoutingEvent;
use Ecotone\Modelling\Config\Routing\RoutingEventHandler;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class AsynchronousModule implements AnnotationModule, RoutingEventHandler
{
    /**
     * @param array<string, array<string>> $asyncEndpoints
     * @param array<string, array<string>> $streamSourcesAsyncEndpoints
     */
    private function __construct(private array $asyncEndpoints, private array $streamSourcesAsyncEndpoints)
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $asynchronousClasses = $annotationRegistrationService->findAnnotatedClasses(Asynchronous::class);

        $asynchronousMethods = $annotationRegistrationService->findAnnotatedMethods(Asynchronous::class);
        $endpoints           = array_merge(
            $annotationRegistrationService->findAnnotatedMethods(EndpointAnnotation::class),
            $annotationRegistrationService->findAnnotatedMethods(EventHandler::class)
        );

        $registeredAsyncEndpoints = [];
        $streamSourcesAsyncEndpoints = [];
        foreach ($asynchronousClasses as $asynchronousClass) {
            /** @var Asynchronous $asyncClass */
            $asyncClass = AnnotatedDefinitionReference::getSingleAnnotationForClass($annotationRegistrationService, $asynchronousClass, Asynchronous::class);
            foreach ($endpoints as $endpoint) {
                if ($asynchronousClass === $endpoint->getClassName()) {
                    /** @var EndpointAnnotation $annotationForMethod */
                    $annotationForMethod = $endpoint->getAnnotationForMethod();
                    if ($annotationForMethod instanceof QueryHandler) {
                        continue;
                    }

                    if ($endpoint->hasClassAnnotation(StreamBasedSource::class)) {
                        $streamSourcesAsyncEndpoints[$annotationForMethod->getEndpointId()] = $asyncClass->getChannelName();
                    } else {
                        if (in_array(get_class($annotationForMethod), [CommandHandler::class, EventHandler::class])) {
                            if ($annotationForMethod->isEndpointIdGenerated()) {
                                throw ConfigurationException::create("{$endpoint} should have endpointId defined for handling asynchronously");
                            }
                        }

                        $registeredAsyncEndpoints[$annotationForMethod->getEndpointId()] = $asyncClass->getChannelName();
                    }
                }
            }
        }

        foreach ($asynchronousMethods as $asynchronousMethod) {
            /** @var Asynchronous $asyncAnnotation */
            $asyncAnnotation = $asynchronousMethod->getAnnotationForMethod();
            foreach ($endpoints as $key => $endpoint) {
                if (($endpoint->getClassName() === $asynchronousMethod->getClassName()) && ($endpoint->getMethodName() === $asynchronousMethod->getMethodName())) {
                    /** @var EndpointAnnotation $annotationForMethod */
                    $annotationForMethod = $endpoint->getAnnotationForMethod();
                    if ($annotationForMethod instanceof QueryHandler) {
                        continue;
                    }
                    if (in_array(get_class($annotationForMethod), [CommandHandler::class, EventHandler::class])) {
                        if ($annotationForMethod->isEndpointIdGenerated()) {
                            throw ConfigurationException::create("{$endpoint} should have endpointId defined for handling asynchronously");
                        }
                    }

                    $registeredAsyncEndpoints[$annotationForMethod->getEndpointId()] = $asyncAnnotation->getChannelName();
                }
            }
        }

        return new self($registeredAsyncEndpoints, $streamSourcesAsyncEndpoints);
    }

    public function getSynchronousChannelFor(string $handlerChannelName, string $endpointIdToLookFor): ?string
    {
        if (array_key_exists($endpointIdToLookFor, $this->asyncEndpoints)) {
            return self::getHandlerExecutionChannel($handlerChannelName);
        }

        return $handlerChannelName;
    }

    public static function getHandlerExecutionChannel(string $originalInputChannelName): string
    {
        return $originalInputChannelName . '.execute';
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof CombinedMessageChannel
            || ($extensionObject instanceof SimpleMessageChannelBuilder && $extensionObject->isPollable())
            || $extensionObject instanceof ServiceConfiguration
            || $extensionObject instanceof PollingMetadata;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $endpointChannels = $this->resolveChannels($extensionObjects);
        $serviceConfiguration = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults());
        $pollingMetadata = ExtensionObjectResolver::resolve(PollingMetadata::class, $extensionObjects);
        $polingChannelBuilders = ExtensionObjectResolver::resolve(SimpleMessageChannelBuilder::class, $extensionObjects);

        foreach ($endpointChannels as $endpointChannel => $asyncChannels) {
            $messagingConfiguration->registerAsynchronousEndpoint($asyncChannels, $endpointChannel);
            $this->registerDefaultPollingMetadata($serviceConfiguration, $asyncChannels, $pollingMetadata, $polingChannelBuilders, $messagingConfiguration);
        }
        foreach ($this->streamSourcesAsyncEndpoints as $endpointChannel => $asyncChannels) {
            $this->registerDefaultPollingMetadata($serviceConfiguration, $asyncChannels, $pollingMetadata, $polingChannelBuilders, $messagingConfiguration);
        }
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        $extensions = [$this];

        if ($serviceConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE)) {
            $polingChannelBuilders = array_map(
                fn (MessageChannelBuilder $channelBuilder) => $channelBuilder->getMessageChannelName(),
                ExtensionObjectResolver::resolve(MessageChannelBuilder::class, $serviceExtensions)
            );
            $endpointChannels = array_reduce(
                $this->resolveChannels($serviceExtensions),
                fn (array $carry, array $item) => array_unique(array_merge($carry, $item)),
                []
            );

            foreach ($endpointChannels as $endpointChannel) {
                if (in_array($endpointChannel, $polingChannelBuilders)) {
                    continue;
                }

                $extensions[] = SimpleMessageChannelBuilder::createQueueChannel(
                    $endpointChannel,
                    true,
                );
            }
        }

        return $extensions;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::ASYNCHRONOUS_PACKAGE;
    }

    private function hasPollingMetadata(array $pollingMetadata, string $asyncEndpoint): bool
    {
        foreach ($pollingMetadata as $pollingMetadataForChannel) {
            if ($pollingMetadataForChannel->getEndpointId() === $asyncEndpoint) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param SimpleMessageChannelBuilder[] $polingChannelBuilders
     */
    private function isInMemoryPollableChannel(array $polingChannelBuilders, string $asyncEndpointChannel): bool
    {
        foreach ($polingChannelBuilders as $polingChannelBuilder) {
            if ($polingChannelBuilder->getMessageChannelName() === $asyncEndpointChannel) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array<string>>
     */
    public function resolveChannels(array $extensionObjects): array
    {
        $combinedMessageChannels = [];
        /** @var CombinedMessageChannel $combinedMessageChannel */
        foreach (ExtensionObjectResolver::resolve(CombinedMessageChannel::class, $extensionObjects) as $combinedMessageChannel) {
            $combinedMessageChannels[$combinedMessageChannel->getReferenceName()] = $combinedMessageChannel->getCombinedChannels();
        }

        $endpointChannels = [];
        foreach ($this->asyncEndpoints as $endpointId => $asyncChannels) {
            $asyncChannels = is_array($asyncChannels) ? $asyncChannels : [$asyncChannels];
            $asyncChannelsResolved = [];
            foreach ($asyncChannels as $asyncChannel) {
                if (array_key_exists($asyncChannel, $combinedMessageChannels)) {
                    $asyncChannelsResolved = array_merge($asyncChannelsResolved, $combinedMessageChannels[$asyncChannel]);
                } else {
                    $asyncChannelsResolved[] = $asyncChannel;
                }
            }
            $endpointChannels[$endpointId] = $asyncChannelsResolved;
        }

        return $endpointChannels;
    }

    public function handleRoutingEvent(RoutingEvent $event): void
    {
        $registration = $event->getRegistration();
        $isAsynchronous = $registration->hasMethodAnnotation(Asynchronous::class);
        if (! $isAsynchronous) {
            return;
        }

        $annotationForMethod = $registration->getAnnotationForMethod();
        $asynchronous = $registration->getMethodAnnotationsWithType(Asynchronous::class)[0];

        if ($annotationForMethod instanceof CommandHandler) {
            Assert::isTrue(! in_array($annotationForMethod->getInputChannelName(), $asynchronous->getChannelName()), "Command Handler routing key can't be equal to asynchronous channel name in {$registration}");
        } elseif ($annotationForMethod instanceof EventHandler) {
            Assert::isTrue(! in_array($annotationForMethod->getListenTo(), $asynchronous->getChannelName()), "Event Handler listen to routing can't be equal to asynchronous channel name in {$registration}");
        }
    }

    public function registerDefaultPollingMetadata(ServiceConfiguration $serviceConfiguration, array $asyncChannels, array $pollingMetadata, array $polingChannelBuilders, Configuration $messagingConfiguration): void
    {
        /** Default polling metadata for tests */
        if ($serviceConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE)) {
            foreach ($asyncChannels as $asyncEndpointChannel) {
                if (! $this->hasPollingMetadata($pollingMetadata, $asyncEndpointChannel)) {
                    if ($this->isInMemoryPollableChannel($polingChannelBuilders, $asyncEndpointChannel)) {
                        $messagingConfiguration->registerPollingMetadata(
                            PollingMetadata::create($asyncEndpointChannel)
                                ->setStopOnError(true)
                                ->setFinishWhenNoMessages(true)
                        );

                        continue;
                    }

                    $messagingConfiguration->registerPollingMetadata(
                        PollingMetadata::create($asyncEndpointChannel)
                            ->withTestingSetup(100, 100, true)
                    );
                }
            }
        }
    }
}
