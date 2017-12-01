<?php

namespace Messaging\Handler\Processor\MethodInvoker;

use Messaging\Handler\MethodArgument;
use Messaging\Message;

/**
 * Class MessageArgument
 * @package Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageArgument implements MethodArgument
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * MessageArgument constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return MessageArgument
     */
    public static function create(string $parameterName) : self
    {
        return new self($parameterName);
    }

    /**
     * @inheritDoc
     */
    public function getFrom(Message $message)
    {
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->parameterName;
    }
}