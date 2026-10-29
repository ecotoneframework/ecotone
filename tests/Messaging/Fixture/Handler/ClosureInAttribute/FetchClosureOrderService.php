<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

use Ecotone\Messaging\Attribute\Parameter\Fetch;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Modelling\Attribute\CommandHandler;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\PlaceOrder;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\User;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\UserNotFound;

/**
 * licence Enterprise
 */
final class FetchClosureOrderService
{
    private array $orders = [];

    #[CommandHandler('placeOrderWithClosureFetch')]
    public function placeOrder(
        PlaceOrder $command,
        #[Fetch(static function (#[Payload] PlaceOrder $command): string {
            return $command->getUserId();
        })] ?User $user
    ): void {
        if ($user === null) {
            throw new UserNotFound('User not found');
        }

        $this->orders[$command->getOrderId()] = [
            'orderId' => $command->getOrderId(),
            'userId' => $user->getUserId(),
            'userName' => $user->getName(),
        ];
    }

    public function getOrder(string $orderId): ?array
    {
        return $this->orders[$orderId] ?? null;
    }
}
