<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Channel\DynamicChannel;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\NullableMessageChannel;
use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
final class DynamicChannelResolver
{
    /**
     * @param string[] $sendingChannelsInOrder
     * @param string[] $receivingChannelsInOrder
     */
    public function __construct(
        private array $sendingChannelsInOrder,
        private array $receivingChannelsInOrder,
    ) {
    }

    /**
     * @return string channel name to consume from
     */
    #[InternalHandler('dynamicChannel.receive')]
    public function toReceive(#[Header('throwException')] bool $throwException = false): string
    {
        if ($throwException) {
            throw new InvalidArgumentException('Exception on sending');
        }

        $channel = array_shift($this->receivingChannelsInOrder);

        return $channel === null ? NullableMessageChannel::CHANNEL_NAME : $channel;
    }

    #[InternalHandler('dynamicChannel.send')]
    public function toSend(): string
    {
        $channel = array_shift($this->sendingChannelsInOrder);

        return $channel === null ? NullableMessageChannel::CHANNEL_NAME : $channel;
    }
}
