<?php

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

use Ecotone\Messaging\Attribute\Parameter\Fetch;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * licence Enterprise
 */
class OrderService
{
    private array $orders = [];

    #[CommandHandler]
    public function placeOrder(
        PlaceOrder $command,
        #[Fetch('payload.getUserId()')] ?User $user
    ): void {
        if ($user === null) {
            throw new UserNotFound('User not found');
        }

        $this->orders[$command->getOrderId()] = [
            'orderId' => $command->getOrderId(),
            'userId' => $user->getUserId(),
            'userName' => $user->getName(),
            'productName' => $command->getProductName(),
        ];
    }

    #[CommandHandler('placeOrderWithHeaders')]
    public function placeOrderWithHeaders(
        PlaceOrder $command,
        #[Fetch("headers['userId']")] ?User $user
    ): void {
        if ($user === null) {
            throw new UserNotFound('User not found');
        }

        $this->orders[$command->getOrderId()] = [
            'orderId' => $command->getOrderId(),
            'userId' => $user->getUserId(),
            'userName' => $user->getName(),
            'productName' => $command->getProductName(),
        ];
    }

    public function getOrder(string $orderId): ?array
    {
        return $this->orders[$orderId] ?? null;
    }
}
