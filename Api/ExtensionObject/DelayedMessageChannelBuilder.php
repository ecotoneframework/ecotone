<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use Ecotone\Api\Scheduling\TimeSpan;

/**
 * licence Apache-2.0
 */
interface DelayedMessageChannelBuilder
{
    public function supportsDelayedMessages(): bool;

    public function maximumDelay(): ?TimeSpan;
}
