<?php

namespace Messaging\Handler\Gateway;
use Messaging\Channel\DirectChannel;
use Messaging\Handler\InterfaceToCall;
use Messaging\Handler\MessageHandlingException;
use Messaging\MessageHeaders;
use Messaging\MessagingException;
use Messaging\Support\Assert;
use Messaging\Support\ErrorMessage;
use Messaging\Support\InvalidArgumentException;

/**
 * Class GatewayProxy
 * @package Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class GatewayProxy
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var MethodCallToMessageConverter
     */
    private $methodCallToMessageConverter;
    /**
     * @var DirectChannel
     */
    private $requestChannel;
    /**
     * @var ReplySender
     */
    private $replySender;

    /**
     * GatewayProxy constructor.
     * @param string $className
     * @param string $methodName
     * @param MethodCallToMessageConverter $methodCallToMessageConverter
     * @param ReplySender $replySender
     * @param DirectChannel|null $requestChannel
     */
    public function __construct(string $className, string $methodName, MethodCallToMessageConverter $methodCallToMessageConverter, ReplySender $replySender, ?DirectChannel $requestChannel)
    {
        $this->methodCallToMessageConverter = $methodCallToMessageConverter;
        $this->className = $className;
        $this->methodName = $methodName;

        $this->initialize($className, $methodName, $replySender);
        $this->requestChannel = $requestChannel;
        $this->replySender = $replySender;
    }

    /**
     * @param array|mixed[] $methodArgumentValues
     * @return mixed
     * @throws \Messaging\MessagingException
     */
    public function execute(array $methodArgumentValues)
    {
        Assert::isInterface($this->className, "Gateway should point to interface instead of got {$this->className}");
        $methodArguments = [];
        $interfaceToCall = InterfaceToCall::create($this->className, $this->methodName);

        $parameters = $interfaceToCall->parameters();
        $countArguments = count($methodArgumentValues);
        for ($index = 0; $index < $countArguments; $index++) {
            $methodArguments[] = MethodArgument::createWith($parameters[$index]->getName(), $methodArgumentValues[$index]);
        }

        $message = $this->methodCallToMessageConverter->convertFor($methodArguments);
        $message = $this->replySender
                        ->addErrorChannel($message)
                        ->build();

        $this->requestChannel->send($message);

        if ($interfaceToCall->doesItReturnFuture()) {
            return FutureReplySender::create($this->replySender);
        }

        $replyMessage = $this->replySender->receiveReply();
        if (is_null($replyMessage) && !$interfaceToCall->doesItReturnValue() && !$interfaceToCall->canItReturnNull()) {
            throw InvalidArgumentException::create("{$interfaceToCall} expects value, but null was returned. Change return type hint to allow nullable values.");
        }

        return $replyMessage ? $replyMessage->getPayload() : null;
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @param ReplySender $replySender
     * @return void
     * @throws \Messaging\MessagingException
     */
    private function initialize(string $interfaceName, string $methodName, ReplySender $replySender) : void
    {
        $interfaceToCall = InterfaceToCall::create($interfaceName, $methodName);
        if ($interfaceToCall->doesItReturnValue() && $replySender->hasReply()) {
            throw InvalidArgumentException::create("Can't create gateway with reply channel, when {$interfaceToCall} is void");
        }
        if (!$interfaceToCall->doesItReturnValue() && !$replySender->hasReply()) {
            throw InvalidArgumentException::create("Interface {$interfaceToCall} has return value, but no reply channel was defined");
        }
    }
}