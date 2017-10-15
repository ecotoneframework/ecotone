<?php

namespace Messaging\Handler\Processor;

use Messaging\Handler\MessageProcessor;
use Messaging\Message;

/**
 * Class MethodInvokingMessageProcessor
 * @package Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInvokingMessageProcessor implements MessageProcessor
{
    /**
     * @var MethodInvocation
     */
    private $methodInvocation;

    /**
     * MethodInvokingMessageProcessor constructor.
     * @param MethodInvocation $methodInvocation
     */
    public function __construct(MethodInvocation $methodInvocation)
    {
        $this->methodInvocation = $methodInvocation;
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        return $this->methodInvocation->processMessage($message);
    }

}