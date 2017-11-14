<?php

namespace Messaging\Handler\ServiceActivator;

use Messaging\MessageChannel;

/**
 * Class CreateServiceActivatorCommand
 * @package Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CreateServiceActivatorCommand
{
    /**
     * @var object
     */
    private $objectToInvokeOn;
    /**
     * @var string
     */
    private $objectMethodName;
    /**
     * @var MessageChannel|null
     */
    private $outputChannel;
    /**
     * @var bool
     */
    private $isReplyRequired;
    /**
     * @var MethodArgument[]
     */
    private $methodArguments;

    public function setObjectToInvokeOn($objectToInvoke) : void
    {
        $this->objectToInvokeOn = $objectToInvoke;
    }

    public function objectToInvokeOn()
    {
        return $this->objectToInvokeOn;
    }

    public function setObjectMethodName(string $objectMethodName) : void
    {
        $this->objectMethodName = $objectMethodName;
    }

    public function objectMethodName() : string
    {
        return $this->objectMethodName;
    }

    /**
     * @param array|MethodArgument[] $methodArguments
     */
    public function setMethodArguments(array $methodArguments) : void
    {
        $this->methodArguments = $methodArguments;
    }

    /**
     * @return array|MethodArgument[]
     */
    public function getMethodArguments() : array
    {
        return $this->methodArguments;
    }

    public function setOutputChannel(MessageChannel $outputChannel) : void
    {
        $this->outputChannel = $outputChannel;
    }

    public function getOutputChannel() : ?MessageChannel
    {
        return $this->outputChannel;
    }

    public function setReplyRequired(bool $isReplyRequired) : void
    {
        $this->isReplyRequired = $isReplyRequired;
    }

    public function isReplyRequired() : bool
    {
        return $this->isReplyRequired;
    }
}