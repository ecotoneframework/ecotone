<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DeliveryConfirmation;

/**
 * licence Enterprise
 */
final class DeliveryResult
{
    /**
     * @param FailedDelivery[] $failedDeliveries
     */
    private function __construct(private array $failedDeliveries)
    {
    }

    public static function successful(): self
    {
        return new self([]);
    }

    /**
     * @param FailedDelivery[] $failedDeliveries
     */
    public static function withFailedDeliveries(array $failedDeliveries): self
    {
        return new self($failedDeliveries);
    }

    public function isSuccessful(): bool
    {
        return $this->failedDeliveries === [];
    }

    /**
     * @return FailedDelivery[]
     */
    public function getFailedDeliveries(): array
    {
        return $this->failedDeliveries;
    }
}
