<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

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
     * @var bool
     */
    private $shouldAdd;

    private $callback;
    /**
     * @var bool
     */
    private $replyWithRequestMessage = false;

    /**
     * StubHttpResponseMessageHandler constructor.
     * @param $replyData
     * @param bool $shouldAdd
     * @param $callback
     */
    private function __construct($replyData, bool $shouldAdd, $callback)
    {
        $this->replyData = $replyData;
        $this->shouldAdd = $shouldAdd;
        $this->callback = $callback;
    }

    public static function create($replyData) : self
    {
        return new self($replyData, false, null);
    }

    public static function createReplyWithRequestMessage() : self
    {
        $replyViaHeadersMessageHandler = new self(null, false, null);
        $replyViaHeadersMessageHandler->replyWithRequestMessage = true;

        return $replyViaHeadersMessageHandler;
    }

    /**
     * @param $callback
     * @return ReplyViaHeadersMessageHandler
     */
    public static function createWithCallback($callback) : self
    {
        return new self(null, false, $callback);
    }

    /**
     * @param $toAdd
     * @return ReplyViaHeadersMessageHandler
     */
    public static function createAdditionToPayload($toAdd) : self
    {
        return new self($toAdd, true, null);
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

            if ($this->replyWithRequestMessage) {
                $replyChannel->send($message);
                return;
            }

            if ($this->replyData || $this->callback) {
                $replyData = $this->replyData ? $this->replyData  : call_user_func($this->callback, $message);
                if ($this->shouldAdd) {
                    $replyData += $message->getPayload();
                }

                if (!is_null($replyData)) {
                    if ($replyData instanceof Message) {
                        $replyChannel->send($replyData);
                        return;
                    }

                    $replyChannel->send(MessageBuilder::fromMessage($message)->setPayload($replyData)->build());
                }
            }
        }
    }

    public function getReceivedMessage() : ?Message
    {
        return $this->message;
    }

    public function __toString()
    {
        return self::class;
    }
}