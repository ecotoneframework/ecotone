<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Api\ExtensionObject\MessageChannelBuilder;

/**
 * licence Apache-2.0
 */
interface HighThroughputPublishingChannelBuilder extends MessageChannelBuilder
{
    public function isBatchPublishingEnabled(): bool;
}
