<?php

namespace Messaging\Handler\Processor;

use Messaging\Message;

/**
 * Class HeaderArgument
 * @package Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderArgument implements MethodArgument
{
    /**
     * @var string
     */
    private $headerName;

    /**
     * HeaderArgument constructor.
     * @param string $headerName
     */
    private function __construct(string $headerName)
    {
        $this->headerName = $headerName;
    }

    /**
     * @param string $headerName
     * @return HeaderArgument
     */
    public static function createWith(string $headerName) : self
    {
        return new self($headerName);
    }

    /**
     * @inheritDoc
     */
    public function getFrom(Message $message)
    {
        return $message->getHeaders()->get($this->headerName);
    }
}