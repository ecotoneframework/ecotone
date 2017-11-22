<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 20.11.17
 * Time: 20:02
 */

namespace Fixture\Handler;


use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageHandler;

class ForwardMessageHandler implements MessageHandler
{
    /**
     * @var MessageChannel
     */
    private $messageChannel;

    /**
     * ForwardMessageHandler constructor.
     * @param MessageChannel $messageChannel
     */
    private function __construct(MessageChannel $messageChannel)
    {
        $this->messageChannel = $messageChannel;
    }

    public static function create(MessageChannel $messageChannel) : self
    {
        return new self($messageChannel);
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
         $this->messageChannel->send($message);
    }
}