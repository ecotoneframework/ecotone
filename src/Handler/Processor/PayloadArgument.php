<?php

namespace Messaging\Handler\Processor;

use Messaging\Message;

/**
 * Class PayloadArgument
 * @package Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadArgument implements MethodArgument
{
    private function __construct()
    {
    }

    public static function create()
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getFrom(Message $message)
    {
        return $message->getPayload();
    }
}