<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation;

use Ecotone\Messaging\MessagingException;

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
final class PublishingFailedException extends MessagingException
{
    /**
     * @param FailedDelivery[] $failedDeliveries
     */
    public static function withFailedDeliveries(array $failedDeliveries): self
    {
        $failureReasons = array_map(
            fn (FailedDelivery $failedDelivery) => $failedDelivery->getFailureReason(),
            $failedDeliveries,
        );

        $exception = new self(sprintf(
            'Failed to deliver %d published message(s): %s',
            count($failedDeliveries),
            implode('; ', array_unique($failureReasons)),
        ));
        $exception->failedDeliveries = $failedDeliveries;

        return $exception;
    }

    /** @var FailedDelivery[] */
    private array $failedDeliveries = [];

    public static function publisherNotConfiguredForDeferredPublishing(string $publisherReference): self
    {
        return new self(sprintf(
            'Message Publisher `%s` is not configured for non blocking confirmation. Call withHighThroughputPublishing() on the publisher configuration to make use of publishDeferred.',
            $publisherReference,
        ));
    }

    /**
     * @return FailedDelivery[]
     */
    public function getFailedDeliveries(): array
    {
        return $this->failedDeliveries;
    }
}
