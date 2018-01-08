<?php

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\MessageChannel;

/**
 * Class SimpleMessageChannelBuilder
 * @package SimplyCodedSoftware\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleMessageChannelBuilder implements MessageChannelBuilder
{
    /**
     * @var string
     */
    private $messageChannelName;
    /**
     * @var MessageChannel
     */
    private $messageChannel;

    /**
     * SimpleMessageChannelBuilder constructor.
     * @param string $messageChannelName
     * @param MessageChannel $messageChannel
     */
    private function __construct(string $messageChannelName, MessageChannel $messageChannel)
    {
        $this->messageChannelName = $messageChannelName;
        $this->messageChannel = $messageChannel;
    }

    /**
     * @param string $messageChannelName
     * @param MessageChannel $messageChannel
     * @return SimpleMessageChannelBuilder
     */
    public static function create(string $messageChannelName, MessageChannel $messageChannel) : self
    {
        return new self($messageChannelName, $messageChannel);
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->messageChannelName;
    }

    /**
     * @inheritDoc
     */
    public function build(): MessageChannel
    {
        return $this->messageChannel;
    }
}