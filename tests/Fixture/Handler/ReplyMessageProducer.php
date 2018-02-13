<?php

namespace Fixture\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class ReplyMessageProducer
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReplyMessageProducer implements \SimplyCodedSoftware\IntegrationMessaging\Handler\MessageProcessor
{
    private $replyData;

    /**
     * ReplyMessageProducer constructor.
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
    public function processMessage(Message $message)
    {
        return $this->replyData;
    }

    public function __toString()
    {
        return self::class;
    }
}