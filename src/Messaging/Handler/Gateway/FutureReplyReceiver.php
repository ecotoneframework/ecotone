<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Future;

/**
 * Class FutureReplySender
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class FutureReplyReceiver implements Future
{
    /**
     * @var SendAndReceiveService
     */
    private $replySender;

    /**
     * FutureReplySender constructor.
     * @param SendAndReceiveService $replySender
     */
    private function __construct(SendAndReceiveService $replySender)
    {
        $this->replySender = $replySender;
    }

    /**
     * @param SendAndReceiveService $replySender
     * @return FutureReplyReceiver
     */
    public static function create(SendAndReceiveService $replySender) : self
    {
        return new self($replySender);
    }

    /**
     * @inheritDoc
     */
    public function resolve()
    {
        $message = $this->replySender->receiveReply();

        return $message ? $message->getPayload() : null;
    }
}