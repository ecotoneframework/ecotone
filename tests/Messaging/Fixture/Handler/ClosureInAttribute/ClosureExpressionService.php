<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
final class ClosureExpressionService
{
    private array $notifications = [];
    private ?int $orderTotal = null;

    #[CommandHandler('notification.send')]
    public function send(
        string $content,
        #[Header('token', expression: static function (#[Header('token')] string $token, #[Reference] UpperCaseService $upperCaseService): string {
            return $upperCaseService->transform($token);
        })] string $token,
    ): void {
        $this->notifications[] = [$content, $token];
    }

    #[CommandHandler('notification.sendWithReversedToken')]
    public function sendWithReversedToken(
        string $content,
        #[Header('token', expression: static function (string $value): string {
            return strrev($value);
        })] string $token,
    ): void {
        $this->notifications[] = [$content, $token];
    }

    #[CommandHandler('order.total')]
    public function calculateTotal(
        #[Payload(expression: static function (#[Payload] array $order, #[Headers] array $headers): int {
            return array_sum($order['items']) + ($headers['fee'] ?? 0);
        })] int $total,
    ): void {
        $this->orderTotal = $total;
    }

    #[QueryHandler('notification.getNotifications')]
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    #[QueryHandler('order.getTotal')]
    public function getTotal(): ?int
    {
        return $this->orderTotal;
    }
}
