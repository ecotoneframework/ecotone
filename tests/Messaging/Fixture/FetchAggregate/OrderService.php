<?php

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Fetch;

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
