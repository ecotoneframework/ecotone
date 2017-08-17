<?php

namespace Messaging\Support;

use Messaging\Message;
use Messaging\MessageHeaders;

/**
 * Class EmptyMessage
 * @package Messaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class EmptyMessage implements Message
{

    private function __construct()
    {
    }

    /**
     * @return Message
     */
    public static function create() : Message
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): MessageHeaders
    {
        return MessageHeaders::createEmpty(0);
    }

    /**
     * @inheritDoc
     */
    public function getPayload()
    {
        return null;
    }
}