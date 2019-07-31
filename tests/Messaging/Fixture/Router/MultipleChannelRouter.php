<?php

namespace Test\Ecotone\Messaging\Fixture\Router;
use Ecotone\Messaging\Message;

/**
 * Class MultipleChannelRouter
 * @package Test\Ecotone\Messaging\Fixture\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MultipleChannelRouter
{
    /**
     * @var array
     */
    private $channelNamesToPick;

    /**
     * SingleChannelRouter constructor.
     * @param array $channelNameToPick
     */
    private function __construct(array $channelNameToPick)
    {
        $this->channelNamesToPick = $channelNameToPick;
    }

    public static function createWithChosenChannelName(array $chanelNames) : self
    {
        return new self($chanelNames);
    }

    public function pick(Message $message) : array
    {
        return $this->channelNamesToPick;
    }
}