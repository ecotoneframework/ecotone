<?php

namespace Ecotone\Enqueue;

use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;

abstract class EnqueueMessageChannelBuilder implements MessageChannelBuilder
{
    public function isPollable(): bool
    {
        return true;
    }

    public function build(ReferenceSearchService $referenceSearchService): MessageChannel
    {
        /** @var ServiceConfiguration|null $applicationConfiguration */
        $applicationConfiguration = $referenceSearchService->has(ServiceConfiguration::class) ? $referenceSearchService->get(ServiceConfiguration::class) : null;
        $pollingMetadata = PollingMetadata::create('');

        if (! $this->getDefaultConversionMediaType() && $applicationConfiguration && $applicationConfiguration->getDefaultSerializationMediaType()) {
            $this->withDefaultConversionMediaType($applicationConfiguration->getDefaultSerializationMediaType());
        }

        if ($applicationConfiguration && $applicationConfiguration->getDefaultErrorChannel()) {
            $pollingMetadata = $pollingMetadata
                ->setErrorChannelName($applicationConfiguration->getDefaultErrorChannel());
        }
        if ($applicationConfiguration && $applicationConfiguration->getDefaultMemoryLimitInMegabytes()) {
            $pollingMetadata = $pollingMetadata
                ->setMemoryLimitInMegaBytes($applicationConfiguration->getDefaultMemoryLimitInMegabytes());
        }
        if ($applicationConfiguration && $applicationConfiguration->getConnectionRetryTemplate()) {
            $pollingMetadata = $pollingMetadata
                ->setConnectionRetryTemplate($applicationConfiguration->getConnectionRetryTemplate());
        }

        return $this->prepareProviderChannel($referenceSearchService, $pollingMetadata);
    }

    abstract public function getDefaultConversionMediaType(): ?MediaType;

    abstract public function withDefaultConversionMediaType(string $mediaType);

    abstract public function prepareProviderChannel(ReferenceSearchService $referenceSearchService, PollingMetadata $pollingMetadata): MessageChannel;
}
