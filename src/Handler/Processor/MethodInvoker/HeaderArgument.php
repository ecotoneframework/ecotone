<?php

namespace Messaging\Handler\Processor\MethodInvoker;

use Messaging\Message;

/**
 * Class HeaderArgument
 * @package Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderArgument implements MethodArgument
{
    /**
     * @var string
     */
    private $headerName;
    /**
     * @var string
     */
    private $argumentName;

    /**
     * HeaderArgument constructor.
     * @param string $argumentName
     * @param string $headerName
     */
    private function __construct(string $argumentName, string $headerName)
    {
        $this->argumentName = $argumentName;
        $this->headerName = $headerName;
    }

    /**
     * @param string $argumentName
     * @param string $headerName
     * @return HeaderArgument
     */
    public static function create(string $argumentName, string $headerName) : self
    {
        return new self($argumentName, $headerName);
    }

    /**
     * @inheritDoc
     */
    public function getFrom(Message $message)
    {
        return $message->getHeaders()->get($this->headerName);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->argumentName;
    }
}