<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class DelayedRetry implements AsynchronousEndpointAttribute, DefinedObject
{
    public function __construct(
        public readonly int     $initialDelayInMilliseconds,
        public readonly int     $multiplier        = 1,
        public readonly ?int    $maxDelayInMilliseconds        = null,
        public readonly ?int    $maxRetries        = 3,
        public readonly ?string $deadLetterChannel = null,
    ) {
        Assert::isTrue($initialDelayInMilliseconds >= 0, "DelayedRetry initialDelayInMilliseconds must be 0 or greater, got {$initialDelayInMilliseconds}");
        Assert::isTrue($multiplier > 0, 'DelayedRetry multiplier must be greater than 0');
        Assert::isTrue($maxRetries === null || $maxRetries > 0, 'DelayedRetry maxRetries must be null (unlimited) or greater than 0');
        Assert::isTrue($deadLetterChannel === null || $deadLetterChannel !== '', 'DelayedRetry deadLetterChannel must be null or a non-empty channel name');
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->initialDelayInMilliseconds,
            $this->multiplier,
            $this->maxDelayInMilliseconds,
            $this->maxRetries,
            $this->deadLetterChannel,
        ]);
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
