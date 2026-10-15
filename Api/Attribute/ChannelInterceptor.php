<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Api\Interceptor\Precedence;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class ChannelInterceptor
{
    public function __construct(
        private string $channelName,
        private bool   $changeHeaders = false,
        private int    $precedence = Precedence::DEFAULT_PRECEDENCE,
    ) {
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    public function isChangeHeaders(): bool
    {
        return $this->changeHeaders;
    }

    public function getPrecedence(): int
    {
        return $this->precedence;
    }
}
