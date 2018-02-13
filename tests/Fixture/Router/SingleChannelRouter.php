<?php

namespace Fixture\Router;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class SingleChannelRouter
 * @package Fixture\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SingleChannelRouter
{
    /**
     * @var string
     */
    private $channelNameToPick;

    /**
     * SingleChannelRouter constructor.
     * @param string $channelNameToPick
     */
    private function __construct(string $channelNameToPick)
    {
        $this->channelNameToPick = $channelNameToPick;
    }

    public static function createWithChosenChannelName(string $chanelName) : self
    {
        return new self($chanelName);
    }

    public function pick(Message $message) : string
    {
        return $this->channelNameToPick;
    }
}