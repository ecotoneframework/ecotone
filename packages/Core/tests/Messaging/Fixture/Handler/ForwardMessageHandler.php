<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 20.11.17
 * Time: 20:02
 */

namespace Test\Ecotone\Messaging\Fixture\Handler;
    

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHandler;

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

    public function __toString()
    {
        return self::class;
    }
}