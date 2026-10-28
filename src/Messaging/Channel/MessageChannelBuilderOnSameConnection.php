<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Api\ExtensionObject\MessageChannelBuilder;

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface MessageChannelBuilderOnSameConnection extends MessageChannelBuilder
{
    public function forSendOnlyChannelOnSameConnection(string $channelName): MessageChannelBuilder;
}
