<?php

namespace Fixture\Handler;

use Psr\Http\Message\ResponseInterface;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class DumbMessageHandler
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Http
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
     * @var bool
     */
    private $shouldAdd;

    /**
     * StubHttpResponseMessageHandler constructor.
     * @param $replyData
     * @param bool $shouldAdd
     */
    private function __construct($replyData, bool $shouldAdd)
    {
        $this->replyData = $replyData;
        $this->shouldAdd = $shouldAdd;
    }

    public static function create($replyData) : self
    {
        return new self($replyData, false);
    }

    /**
     * @param $toAdd
     * @return ReplyViaHeadersMessageHandler
     */
    public static function createAdditionToPayload($toAdd) : self
    {
        return new self($toAdd, true);
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