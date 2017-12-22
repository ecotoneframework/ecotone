<?php

namespace Messaging\Handler\Gateway;

use Messaging\Future;

/**
 * Class FutureReplySender
 * @package Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class FutureReplySender implements Future
{
    /**
     * @var ReplySender
     */
    private $replySender;

    /**
     * FutureReplySender constructor.
     * @param ReplySender $replySender
     */
    private function __construct(ReplySender $replySender)
    {
        $this->replySender = $replySender;
    }

    /**
     * @param ReplySender $replySender
     * @return FutureReplySender
     */
    public static function create(ReplySender $replySender) : self
    {
        return new self($replySender);
    }

    /**
     * @inheritDoc
     */
    public function resolve()
    {
        return $this->replySender->receiveReply()->getPayload();
    }
}