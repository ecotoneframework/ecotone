<?php

namespace Messaging\Handler\Gateway;
use Messaging\Channel\DirectChannel;
use Messaging\Support\InvalidArgumentException;

/**
 * Class GatewayProxy
 * @package Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
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
        $methodArguments = [];
        $interfaceToCall = InterfaceToCall::create($this->className, $this->methodName);

        $parameters = $interfaceToCall->parameters();
        $countArguments = count($methodArgumentValues);
        for ($index = 0; $index < $countArguments; $index++) {
            $methodArguments[] = MethodArgument::createWith($parameters[$index]->getName(), $methodArgumentValues[$index]);
        }

        $message = $this->methodCallToMessageConverter->convertFor($methodArguments);

        $this->requestChannel->send($message);

        $replyMessage = $this->replySender->receiveReply();

        if (is_null($replyMessage)) {
            return null;
        }

        return $replyMessage->getPayload();
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
        if ($interfaceToCall->isVoid() && $replySender->hasReply()) {
            throw InvalidArgumentException::create("Can't create gateway with reply channel, when {$interfaceToCall} is void");
        }
        if (!$interfaceToCall->isVoid() && !$replySender->hasReply()) {
            throw InvalidArgumentException::create("Interface {$interfaceToCall} has return value, but no reply channel was defined");
        }
    }
}