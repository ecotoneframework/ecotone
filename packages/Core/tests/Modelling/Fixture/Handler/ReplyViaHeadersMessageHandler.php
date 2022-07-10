<?php

namespace Test\Ecotone\Modelling\Fixture\Handler;

use Psr\Http\Message\ResponseInterface;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class DumbMessageHandler
 * @package Test\Ecotone\Messaging\Http
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReplyViaHeadersMessageHandler implements MessageHandler
{
    /**
     * @var Message|null
     */
    private $message;
    /**
     * @var mixed
     */
    private $replyData;

    /**
     * StubHttpResponseMessageHandler constructor.
     * @param $replyData
     */
    private function __construct($replyData)
    {
        $this->replyData = $replyData;
    }

    public static function create($replyData) : self
    {
        return new self($replyData);
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $this->message = $message;

        if ($message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            /** @var MessageChannel $replyChannel */
            $replyChannel = $message->getHeaders()->getReplyChannel();
            if (!is_null($this->replyData)) {
                if ($this->replyData instanceof Message) {
                    $replyChannel->send($this->replyData);
                    return;
                }

                $replyChannel->send(MessageBuilder::withPayload($this->replyData)->build());
            }
        }
    }

    public function getReceivedMessage() : ?Message
    {
        return $this->message;
    }
}