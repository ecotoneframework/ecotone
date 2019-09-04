<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Annotation\ChannelAdapter;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Transaction\Transactional;

/**
 * Class PollerModule
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class PollerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = "pollerModule";
    /**
     * @var array|PollingMetadata[]
     */
    private $multiplePollingMetadata;

    /**
     * PollerModule constructor.
     * @param array|PollingMetadata[] $multiplePollingMetadata
     */
    private function __construct(array $multiplePollingMetadata)
    {
        $this->multiplePollingMetadata = $multiplePollingMetadata;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        $multiplePollingMetadata = [];

        $endpoints = array_merge(
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EndpointAnnotation::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, ChannelAdapter::class)
        );
        foreach ($endpoints as $endpoint) {
            /** @var EndpointAnnotation $endpointAnnotation */
            $endpointAnnotation = $endpoint->getAnnotationForMethod();
            $poller = $endpointAnnotation->poller;

            if ($poller) {
                $multiplePollingMetadata[] = PollingMetadata::create($endpointAnnotation->endpointId)
                    ->setCron($poller->cron)
                    ->setInitialDelayInMilliseconds($poller->initialDelayInMilliseconds)
                    ->setFixedRateInMilliseconds($poller->fixedRateInMilliseconds)
                    ->setErrorChannelName($poller->errorChannelName)
                    ->setMaxMessagePerPoll($poller->maxMessagePerPoll)
                    ->setMemoryLimitInMegaBytes($poller->memoryLimitInMegabytes)
                    ->setHandledMessageLimit($poller->handledMessageLimit)
                    ->setExecutionTimeLimitInMilliseconds($poller->executionTimeLimitInMilliseconds);
            }
        }

        return new self($multiplePollingMetadata);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        foreach ($this->multiplePollingMetadata as $pollingMetadata) {
            $configuration->registerPollingMetadata($pollingMetadata);
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }
}