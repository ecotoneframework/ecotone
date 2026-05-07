<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Enterprise
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class DelayedRetry implements AsynchronousEndpointAttribute
{
    public function __construct(
        public readonly int     $initialDelayMs,
        public readonly int     $multiplier        = 1,
        public readonly ?int    $maxDelayMs        = null,
        public readonly ?int    $maxAttempts       = 3,
        public readonly ?string $deadLetterChannel = null,
    ) {
        Assert::isTrue($initialDelayMs > 0, 'DelayedRetry initialDelayMs must be greater than 0');
        Assert::isTrue($multiplier > 0, 'DelayedRetry multiplier must be greater than 0');
        Assert::isTrue($maxAttempts === null || $maxAttempts > 0, 'DelayedRetry maxAttempts must be null (unlimited) or greater than 0');
        Assert::isTrue($deadLetterChannel === null || $deadLetterChannel !== '', 'DelayedRetry deadLetterChannel must be null or a non-empty channel name');
    }

    public static function generateChannelName(string $handlerEndpointId): string
    {
        return 'ecotone.retry.' . $handlerEndpointId;
    }

    public static function generateGatewayChannelName(string $gatewayInterfaceFqn): string
    {
        return 'ecotone.retry.gateway.' . str_replace('\\', '.', $gatewayInterfaceFqn);
    }
}
