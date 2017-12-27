<?php

namespace Messaging\Config;

use Messaging\MessageChannel;

/**
 * Class ResolvableChannel
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ResolvableChannel
{
    /**
     * @var string
     */
    private $channelName;
    /**
     * @var MessageChannel
     */
    private $messageChannel;

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
     * @return ResolvableChannel
     */
    public static function create(string $channelName, MessageChannel $messageChannel) : self
    {
        return new self($channelName, $messageChannel);
    }

    /**
     * @return MessageChannel
     */
    public function getMessageChannel() : MessageChannel
    {
        return $this->messageChannel;
    }

    /**
     * @param string $channelName
     * @return bool
     */
    public function hasName(string $channelName) : bool
    {
        return $this->channelName === $channelName;
    }
}