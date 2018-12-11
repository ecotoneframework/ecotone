<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor;

use SimplyCodedSoftware\Messaging\Handler\MessageProcessor;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class ThrowExceptionMessageProcessor
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ThrowExceptionMessageProcessor implements MessageProcessor
{
    /**
     * @var \Throwable
     */
    private $exception;

    private function __construct(\Throwable $exception)
    {
        $this->exception = $exception;
    }

    public static function create(\Throwable $exception) : self
    {
        return new self($exception);
    }

    /**
     * @inheritDoc
     */
    public function processMessage(Message $message)
    {
        throw $this->exception;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return self::class;
    }
}