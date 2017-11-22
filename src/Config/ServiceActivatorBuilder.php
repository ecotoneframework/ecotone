<?php

namespace Messaging\Config;

use Messaging\Handler\ServiceActivator\MethodArgument;
use Messaging\Handler\ServiceActivator\MethodInvoker;
use Messaging\Handler\ServiceActivator\RequestReplyProducer;
use Messaging\Handler\ServiceActivator\ServiceActivatingHandler;
use Messaging\MessageChannel;

/**
 * Class ServiceActivatorFactory
 * @package Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorBuilder
{
    /**
     * @var object
     */
    private $objectToInvokeOn;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var MessageChannel
     */
    private $outputChannel;
    /**
     * @var  bool
     */
    private $isReplyRequired = false;
    /**
     * @var array|MethodArgument[]
     */
    private $methodArguments = [];

    /**
     * ServiceActivatorBuilder constructor.
     * @param $objectToInvokeOn
     * @param string $methodName
     */
    private function __construct($objectToInvokeOn, string $methodName)
    {
        $this->objectToInvokeOn = $objectToInvokeOn;
        $this->methodName = $methodName;
    }

    /**
     * @param $objectToInvokeOn
     * @param string $methodName
     * @return ServiceActivatorBuilder
     */
    public static function create($objectToInvokeOn, string $methodName) : self
    {
        return new self($objectToInvokeOn, $methodName);
    }

    /**
     * @param bool $isReplyRequired
     * @return ServiceActivatorBuilder
     */
    public function withRequiredReply(bool $isReplyRequired) : self
    {
        $this->isReplyRequired = $isReplyRequired;

        return $this;
    }

    /**
     * @param MessageChannel $messageChannel
     * @return ServiceActivatorBuilder
     */
    public function withOutputChannel(MessageChannel $messageChannel) : self
    {
        $this->outputChannel = $messageChannel;

        return $this;
    }

    /**
     * @param array|MethodArgument[] $methodArguments
     * @return ServiceActivatorBuilder
     */
    public function withMethodArguments(array $methodArguments) : self
    {
        $this->methodArguments = $methodArguments;

        return $this;
    }

    /**
     * @return ServiceActivatingHandler
     */
    public function build() : ServiceActivatingHandler
    {
        return new ServiceActivatingHandler(
            new RequestReplyProducer(
                $this->outputChannel,
                $this->isReplyRequired
            ),
            MethodInvoker::createWith(
                $this->objectToInvokeOn,
                $this->methodName,
                $this->methodArguments
            )
        );
    }
}