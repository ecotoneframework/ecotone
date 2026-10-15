<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation;

use Ecotone\Api\Messaging\Future;
use Throwable;

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
final class DeliveryFuture implements Future
{
    private bool $resolved = false;

    private ?PublishingFailedException $failure = null;

    /**
     * @param PendingDelivery[] $pendingDeliveries
     */
    private function __construct(private array $pendingDeliveries)
    {
    }

    /**
     * @param PendingDelivery[] $pendingDeliveries
     */
    public static function forPendingDeliveries(array $pendingDeliveries): self
    {
        return new self($pendingDeliveries);
    }

    public function resolve()
    {
        if ($this->resolved) {
            if ($this->failure !== null) {
                throw $this->failure;
            }

            return;
        }

        $this->resolved = true;
        $failedDeliveries = [];
        $awaitFailure = null;
        foreach ($this->pendingDeliveries as $pendingDelivery) {
            try {
                $deliveryResult = $pendingDelivery->awaitDelivery();
            } catch (Throwable $exception) {
                $awaitFailure ??= $exception;

                continue;
            }

            if (! $deliveryResult->isSuccessful()) {
                $failedDeliveries = array_merge($failedDeliveries, $deliveryResult->getFailedDeliveries());
            }
        }

        if ($awaitFailure !== null) {
            $this->failure = $awaitFailure instanceof PublishingFailedException && $failedDeliveries === []
                ? $awaitFailure
                : new PublishingFailedException(sprintf('Awaiting delivery confirmation failed: %s', $awaitFailure->getMessage()), 0, $awaitFailure);

            throw $this->failure;
        }

        if ($failedDeliveries !== []) {
            $this->failure = PublishingFailedException::withFailedDeliveries($failedDeliveries);

            throw $this->failure;
        }
    }
}
