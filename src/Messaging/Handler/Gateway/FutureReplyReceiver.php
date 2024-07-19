<?php

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Future;
use Ecotone\Messaging\Message;

/**
 * Class FutureReplySender
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class FutureReplyReceiver implements Future
{
    /**
     * @var callable
     */
    private $replyCallable;

    /**
     * FutureReplySender constructor.
     * @param callable $replyCallable
     */
    private function __construct(callable $replyCallable)
    {
        $this->replyCallable = $replyCallable;
    }

    /**
     * @param callable $replyCallable
     * @return FutureReplyReceiver
     */
    public static function create(callable $replyCallable): self
    {
        return new self($replyCallable);
    }

    /**
     * @inheritDoc
     */
    public function resolve()
    {
        $replyCallable = $this->replyCallable;
        /** @var Message $message */
        $message = $replyCallable();

        return $message ? $message->getPayload() : null;
    }
}
