<?php

namespace Messaging\Handler\Processor\TimeoutProcessor;

use Messaging\Handler\MessageProcessor;
use Messaging\Message;

/**
 * Class TimeoutMessageProcessor
 * @package Messaging\Handler\Processor\TimeoutProcessor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TimeoutMessageProcessor implements MessageProcessor
{
    /**
     * @var MessageProcessor
     */
    private $messageProcessor;
    /**
     * @var int
     */
    private $timeoutMilliseconds;

    /**
     * TimeoutMessageProcessor constructor.
     * @param MessageProcessor $messageProcessor
     * @param int $timeoutMilliseconds
     */
    public function __construct(MessageProcessor $messageProcessor, int $timeoutMilliseconds)
    {
        $this->messageProcessor = $messageProcessor;
        $this->timeoutMilliseconds = $timeoutMilliseconds;
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        return $this->messageProcessor->processMessage($message);
    }
}