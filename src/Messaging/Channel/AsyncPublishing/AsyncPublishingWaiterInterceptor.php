<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\AsyncPublishing;

use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Enterprise
 */
final class AsyncPublishingWaiterInterceptor
{
    /**
     * @param array<string, string|null> $errorChannels
     */
    public function __construct(
        private AsyncPublishingRegistry $asyncPublishingRegistry,
        private array $errorChannels,
        private ?string $globalErrorChannelName,
        private ErrorChannelService $errorChannelService,
        private ConfiguredMessagingSystem $configuredMessagingSystem,
    ) {
    }

    public function await(MethodInvocation $methodInvocation): mixed
    {
        if ($this->asyncPublishingRegistry->isScopeActive()) {
            return $methodInvocation->proceed();
        }

        $this->asyncPublishingRegistry->openScope();
        try {
            $result = $methodInvocation->proceed();

            $deliveryResult = $this->asyncPublishingRegistry->awaitAll();
            if (! $deliveryResult->isSuccessful()) {
                $unroutedFailedDeliveries = $this->handleFailedDeliveries($deliveryResult->getFailedDeliveries());

                $errorChannelDeliveryResult = $this->asyncPublishingRegistry->awaitAll();
                $remainingFailedDeliveries = array_merge($unroutedFailedDeliveries, $errorChannelDeliveryResult->getFailedDeliveries());
                if ($remainingFailedDeliveries !== []) {
                    throw PublishingFailedException::withFailedDeliveries($remainingFailedDeliveries);
                }
            }
        } finally {
            $this->asyncPublishingRegistry->closeScope();
        }

        return $result;
    }

    /**
     * @param FailedDelivery[] $failedDeliveries
     * @return FailedDelivery[]
     */
    private function handleFailedDeliveries(array $failedDeliveries): array
    {
        $unroutedFailedDeliveries = [];
        foreach ($failedDeliveries as $failedDelivery) {
            $errorChannelName = array_key_exists($failedDelivery->getChannelName(), $this->errorChannels)
                ? $this->errorChannels[$failedDelivery->getChannelName()]
                : $this->globalErrorChannelName;

            if ($errorChannelName === null) {
                $unroutedFailedDeliveries[] = $failedDelivery;

                continue;
            }

            foreach ($this->unpackFailedMessages($failedDelivery->getMessage()) as $failedMessage) {
                $this->errorChannelService->handle(
                    $failedMessage,
                    PublishingFailedException::withFailedDeliveries([$failedDelivery]),
                    $this->configuredMessagingSystem->getMessageChannelByName($errorChannelName),
                    $failedDelivery->getChannelName(),
                );
            }
        }

        return $unroutedFailedDeliveries;
    }

    /**
     * @return Message[]
     */
    private function unpackFailedMessages(Message $message): array
    {
        $payload = $message->getPayload();
        if (! $payload instanceof BatchMessage) {
            return [$message];
        }

        return array_map(
            fn (array $entry): Message => MessageBuilder::withPayload($entry['payload'])
                ->setMultipleHeaders($entry['headers'])
                ->build(),
            $payload->getEntries(),
        );
    }
}
