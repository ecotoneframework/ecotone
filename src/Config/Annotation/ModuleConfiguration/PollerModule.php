<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingMetadata;

/**
 * Class PollerModule
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration
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

        $endpoints = $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EndpointAnnotation::class);

        foreach ($endpoints as $endpoint) {
            /** @var EndpointAnnotation $endpointAnnotation */
            $endpointAnnotation = $endpoint->getAnnotationForMethod();
            $poller = $endpointAnnotation->poller;

            if ($poller) {
                $multiplePollingMetadata[] = PollingMetadata::create($endpointAnnotation->endpointId)
                    ->setCron($poller->cron)
                    ->setInitialDelayInMilliseconds($poller->initialDelayInMilliseconds)
                    ->setFixedRateInMilliseconds($poller->fixedRateInMilliseconds)
                    ->setTransactionFactoryReferenceNames($poller->transactionFactoryReferenceNames)
                    ->setErrorChannelName($poller->errorChannelName)
                    ->setMaxMessagePerPoll($poller->maxMessagePerPoll)
                    ->setTriggerReferenceName($poller->triggerReferenceName)
                    ->setTaskExecutorName($poller->taskExecutorName);
            }
        }

        return new self($multiplePollingMetadata);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions): void
    {
        foreach ($this->multiplePollingMetadata as $pollingMetadata) {
            $configuration->registerPollingMetadata($pollingMetadata);
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }
}