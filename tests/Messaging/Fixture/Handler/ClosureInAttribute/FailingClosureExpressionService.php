<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Payload;
use RuntimeException;

/**
 * licence Apache-2.0
 */
final class FailingClosureExpressionService
{
    #[CommandHandler('closureExpressionFailure.throwing')]
    public function withThrowingClosure(
        #[Payload(expression: static function (string $orderId): string {
            throw new RuntimeException('closure blew up for ' . $orderId);
        })] string $orderId,
    ): void {
    }
}
