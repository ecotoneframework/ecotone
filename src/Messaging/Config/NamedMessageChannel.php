<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\MessageChannel;

/**
 * Class ResolvableChannel
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class NamedMessageChannel
{
    private string $channelName;
    private MessageChannel $messageChannel;

    /**
     * ResolvableChannel constructor.
     * @param string $channelName
     * @param MessageChannel $messageChannel
     */
    private function __construct(string $channelName, MessageChannel $messageChannel)
    {
        $this->channelName = $channelName;
        $this->messageChannel = $messageChannel;
    }

    /**
     * @param string $channelName
     * @param MessageChannel $messageChannel
     * @return NamedMessageChannel
     */
    public static function create(string $channelName, MessageChannel $messageChannel): self
    {
        return new self($channelName, $messageChannel);
    }

    /**
     * @return MessageChannel
     */
    public function getMessageChannel(): MessageChannel
    {
        return $this->messageChannel;
    }

    /**
     * @param string|null $channelName
     * @return bool
     */
    public function hasName(?string $channelName): bool
    {
        return $this->channelName == $channelName;
    }

    public function getName(): string
    {
        return $this->channelName;
    }
}
