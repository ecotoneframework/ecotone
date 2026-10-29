<?php

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

/**
 * licence Enterprise
 */
class PlaceOrder
{
    public function __construct(
        private string $orderId,
        private ?string $userId,
        private string $productName
    ) {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }
}
