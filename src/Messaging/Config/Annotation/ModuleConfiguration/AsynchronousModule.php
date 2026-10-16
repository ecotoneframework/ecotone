<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\CombinedMessageChannel;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\StreamBasedSource;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\BootstrapConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class AsynchronousModule implements AnnotationModule
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
                        if ($annotationForMethod instanceof CommandHandler || $annotationForMethod instanceof EventHandler || $annotationForMethod instanceof InternalHandler) {
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
                    if ($annotationForMethod instanceof CommandHandler || $annotationForMethod instanceof EventHandler || $annotationForMethod instanceof InternalHandler) {
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
    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $endpointChannels = $this->resolveChannels($extensionObjects);
        $bootstrapConfiguration = ExtensionObjectResolver::resolveUnique(BootstrapConfiguration::class, $extensionObjects, BootstrapConfiguration::createWithDefaults());
        $pollingMetadata = ExtensionObjectResolver::resolve(PollingMetadata::class, $extensionObjects);
        $polingChannelBuilders = ExtensionObjectResolver::resolve(SimpleMessageChannelBuilder::class, $extensionObjects);

        foreach ($endpointChannels as $endpointChannel => $asyncChannels) {
            $messagingConfiguration->registerAsynchronousEndpoint($asyncChannels, $endpointChannel);
            $this->registerDefaultPollingMetadata($bootstrapConfiguration, $asyncChannels, $pollingMetadata, $polingChannelBuilders, $messagingConfiguration);
        }
        foreach ($this->streamSourcesAsyncEndpoints as $endpointChannel => $asyncChannels) {
            $asyncChannels = is_array($asyncChannels) ? $asyncChannels : [$asyncChannels];
            $this->registerDefaultPollingMetadata($bootstrapConfiguration, $asyncChannels, $pollingMetadata, $polingChannelBuilders, $messagingConfiguration);
        }
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        $bootstrapConfiguration = ExtensionObjectResolver::resolveUnique(BootstrapConfiguration::class, $serviceExtensions, BootstrapConfiguration::createWithDefaults());
        if (! $bootstrapConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE)) {
            return [$this];
        }

        return array_merge([$this], $this->inMemoryChannelsForUnconfiguredAsynchronousChannels($serviceExtensions));
    }

    /**
     * @param object[] $serviceExtensions
     * @return SimpleMessageChannelBuilder[]
     */
    private function inMemoryChannelsForUnconfiguredAsynchronousChannels(array $serviceExtensions): array
    {
        $configuredChannelNames = array_map(
            fn (MessageChannelBuilder $channelBuilder) => $channelBuilder->getMessageChannelName(),
            ExtensionObjectResolver::resolve(MessageChannelBuilder::class, $serviceExtensions)
        );

        $asynchronousChannelNames = [];
        foreach ($this->resolveChannels($serviceExtensions) as $asyncChannels) {
            $asynchronousChannelNames = array_merge($asynchronousChannelNames, $asyncChannels);
        }
        foreach ($this->streamSourcesAsyncEndpoints as $asyncChannels) {
            $asynchronousChannelNames = array_merge($asynchronousChannelNames, is_array($asyncChannels) ? $asyncChannels : [$asyncChannels]);
        }

        return array_map(
            fn (string $channelName) => SimpleMessageChannelBuilder::createQueueChannel($channelName),
            array_values(array_diff(array_unique($asynchronousChannelNames), $configuredChannelNames))
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
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
            $combinedMessageChannels[$combinedMessageChannel->getReferenceName()] = $combinedMessageChannel->combinedChannels;
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

    public function registerDefaultPollingMetadata(BootstrapConfiguration $bootstrapConfiguration, array $asyncChannels, array $pollingMetadata, array $polingChannelBuilders, Configuration $messagingConfiguration): void
    {
        /** Default polling metadata for tests */
        if ($bootstrapConfiguration->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE)) {
            foreach ($asyncChannels as $asyncEndpointChannel) {
                if (! $this->hasPollingMetadata($pollingMetadata, $asyncEndpointChannel)) {
                    if ($this->isInMemoryPollableChannel($polingChannelBuilders, $asyncEndpointChannel)) {
                        $messagingConfiguration->registerPollingMetadata(
                            PollingMetadata::create($asyncEndpointChannel)
                                ->withStopOnError(true)
                                ->withFinishWhenNoMessages(true)
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
