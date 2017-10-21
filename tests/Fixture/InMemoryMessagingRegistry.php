<?php

namespace Fixture;

use Messaging\MessageChannel;
use Messaging\MessagingRegistry;

/**
 * Class DumbMessagingRegistry
 * @package Fixture
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryMessagingRegistry implements MessagingRegistry
{
    /**
     * @var array|MessageChannel
     */
    private $messageChannels = [];

    public function __construct(array $messageChannels = [])
    {
        foreach ($messageChannels as $messageChannelName => $messageChannel) {
            $this->saveMessageChannel($messageChannelName, $messageChannel);
        }
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannel($messageChannel): MessageChannel
    {
        if ($messageChannel instanceof MessageChannel) {
            return $messageChannel;
        }

        return $this->messageChannels[$messageChannel];
    }

    private function saveMessageChannel(string $name, MessageChannel $messageChannel) : void
    {
        $this->messageChannels[$name] = $messageChannel;
    }
}