<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation;

use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\BatchMessage;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Support\MessageBuilder;

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
 */
final class DeliveryConfirmationInterceptor
{
    /**
     * @param array<string, string|null> $errorChannels
     */
    public function __construct(
        private PendingDeliveryRegistry $pendingDeliveryRegistry,
        private array $errorChannels,
        private ?string $globalErrorChannelName,
        private ErrorChannelService $errorChannelService,
        private ConfiguredMessagingSystem $configuredMessagingSystem,
    ) {
    }

    public function await(MethodInvocation $methodInvocation): mixed
    {
        if ($this->pendingDeliveryRegistry->isScopeActive()) {
            return $methodInvocation->proceed();
        }

        $this->pendingDeliveryRegistry->openScope();
        try {
            $result = $methodInvocation->proceed();

            $deliveryResult = $this->pendingDeliveryRegistry->awaitAll();
            if (! $deliveryResult->isSuccessful()) {
                $unroutedFailedDeliveries = $this->handleFailedDeliveries($deliveryResult->getFailedDeliveries());

                $errorChannelDeliveryResult = $this->pendingDeliveryRegistry->awaitAll();
                $remainingFailedDeliveries = array_merge($unroutedFailedDeliveries, $errorChannelDeliveryResult->getFailedDeliveries());
                if ($remainingFailedDeliveries !== []) {
                    throw PublishingFailedException::withFailedDeliveries($remainingFailedDeliveries);
                }
            }
        } finally {
            $this->pendingDeliveryRegistry->closeScope();
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
            $payload->entries,
        );
    }
}
