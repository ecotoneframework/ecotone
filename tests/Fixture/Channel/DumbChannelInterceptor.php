<?php

namespace Fixture\Channel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Class DumbChannelInterceptor
 * @package Fixture\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DumbChannelInterceptor implements ChannelInterceptor
{
    /**
     * @var Message|null
     */
    private $preSendMessage;
    /**
     * @var
     */
    private $postSendWasCalled = false;
    /**
     * @var bool
     */
    private $postSendWasCalledWithSuccessful = false;

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function preSend(?Message $message, MessageChannel $messageChannel): ?Message
    {
        // TODO: Implement preSend() method.
    }

    /**
     * @inheritDoc
     */
    public function postSend(?Message $message, MessageChannel $messageChannel, bool $wasSuccessful): void
    {
        // TODO: Implement postSend() method.
    }

    /**
     * @inheritDoc
     */
    public function preReceive(MessageChannel $messageChannel): void
    {
        // TODO: Implement preReceive() method.
    }

    /**
     * @inheritDoc
     */
    public function postReceive(?Message $message, MessageChannel $messageChannel): void
    {
        // TODO: Implement postReceive() method.
    }
}