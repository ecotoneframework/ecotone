<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ChannelAdapter;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\Poller;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class PollerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    private array $pollingMetadata;

    /**
     * @var PollingMetadata[] $pollingMetadata
     */
    public function __construct(array $pollingMetadata)
    {
        $this->pollingMetadata = $pollingMetadata;
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $multiplePollingMetadata = [];

        $pollers = $annotationRegistrationService->findAnnotatedMethods(Poller::class);
        foreach ($pollers as $pollerAnnotation) {
            /** @var Poller $poller */
            $poller = $pollerAnnotation->getAnnotationForMethod();
            if (! $pollerAnnotation->hasMethodAnnotation(ChannelAdapter::class)) {
                throw ConfigurationException::create("Poller {$pollerAnnotation} attribute is not connected with any endpoint");
            }

            /** @var ChannelAdapter[] $channelAdapters */
            $channelAdapters = $pollerAnnotation->getMethodAnnotationsWithType(ChannelAdapter::class);
            foreach ($channelAdapters as $channelAdapter) {
                $pollingMetadata = PollingMetadata::create($channelAdapter->getEndpointId())
                    ->setCron($poller->getCron())
                    ->setInitialDelayInMilliseconds($poller->getInitialDelayInMilliseconds())
                    ->setFixedRateInMilliseconds($poller->getFixedRateInMilliseconds())
                    ->setErrorChannelName($poller->getErrorChannelName())
                    ->setMemoryLimitInMegaBytes($poller->getMemoryLimitInMegabytes())
                    ->setHandledMessageLimit($poller->getHandledMessageLimit())
                    ->setExecutionTimeLimitInMilliseconds($poller->getExecutionTimeLimitInMilliseconds())
                    ->setFixedRateExpression($poller->getFixedRateExpression())
                    ->setCronExpression($poller->getCronExpression());

                $multiplePollingMetadata[] = $pollingMetadata;
            }
        }

        return new self($multiplePollingMetadata);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->pollingMetadata as $metadata) {
            $messagingConfiguration->registerPollingMetadata($metadata);
        }
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
