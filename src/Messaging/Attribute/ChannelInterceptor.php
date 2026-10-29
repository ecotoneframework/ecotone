<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
final class ChannelInterceptor
{
    public function __construct(
        private string $channelName,
        private bool   $changeHeaders = false,
    ) {
    }
}
