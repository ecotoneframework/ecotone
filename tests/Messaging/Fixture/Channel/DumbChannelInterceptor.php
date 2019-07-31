<?php

namespace Test\Ecotone\Messaging\Fixture\Channel;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;

/**
 * Class DumbChannelInterceptor
 * @package Test\Ecotone\Messaging\Fixture\Channel
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
    public function preSend(Message $message, MessageChannel $messageChannel): ?Message
    {
        // TODO: Implement preSend() method.
    }

    /**
     * @inheritDoc
     */
    public function postSend(Message $message, MessageChannel $messageChannel): void
    {
        // TODO: Implement postSend() method.
    }

    /**
     * @inheritDoc
     */
    public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?\Throwable $exception): void
    {
        // TODO: Implement afterSendCompletion() method.
    }

    /**
     * @inheritDoc
     */
    public function preReceive(MessageChannel $messageChannel): bool
    {
        // TODO: Implement preReceive() method.
    }

    /**
     * @inheritDoc
     */
    public function postReceive(Message $message, MessageChannel $messageChannel): ?Message
    {
        // TODO: Implement postReceive() method.
    }

    /**
     * @inheritDoc
     */
    public function afterReceiveCompletion(?Message $message, MessageChannel $messageChannel, ?\Throwable $exception) : void
    {
        // TODO: Implement afterReceiveCompletion() method.
    }
}