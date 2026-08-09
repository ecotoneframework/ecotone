<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation;

use Ecotone\Messaging\Future;
use Throwable;

/**
 * licence Enterprise
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
