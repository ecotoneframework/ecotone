<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class GatewayProxy
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
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
     * @param DirectChannel $requestChannel
     */
    public function __construct(string $className, string $methodName, MethodCallToMessageConverter $methodCallToMessageConverter, ReplySender $replySender, DirectChannel $requestChannel)
    {
        $this->methodCallToMessageConverter = $methodCallToMessageConverter;
        $this->className = $className;
        $this->methodName = $methodName;

//        $this->initialize($className, $methodName, $replySender);
        $this->requestChannel = $requestChannel;
        $this->replySender = $replySender;
    }

    /**
     * @param array|mixed[] $methodArgumentValues
     * @return mixed
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function execute(array $methodArgumentValues)
    {
        Assert::isInterface($this->className, "Gateway should point to interface instead of got {$this->className}");
        $methodArguments = [];
        $interfaceToCall = InterfaceToCall::create($this->className, $this->methodName);

        $parameters = $interfaceToCall->parameters();
        $countArguments = count($methodArgumentValues);
        for ($index = 0; $index < $countArguments; $index++) {
            $methodArguments[] = MethodArgument::createWith($parameters[$index], $methodArgumentValues[$index]);
        }

        $message = $this->methodCallToMessageConverter->convertFor($methodArguments);
        $message = $this->replySender
                        ->prepareFor($interfaceToCall, $message)
                        ->build();

        $this->requestChannel->send($message);

        if ($interfaceToCall->doesItReturnFuture()) {
            return FutureReplySender::create($this->replySender);
        }

        $replyMessage = $this->replySender->receiveReply();
        if (is_null($replyMessage) && !$interfaceToCall->doesItNotReturnValue() && !$interfaceToCall->canItReturnNull()) {
            throw InvalidArgumentException::create("{$interfaceToCall} expects value, but null was returned. Change return type hint to allow nullable values.");
        }

        return $replyMessage ? $replyMessage->getPayload() : null;
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @param ReplySender $replySender
     * @return void
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $interfaceName, string $methodName, ReplySender $replySender) : void
    {
        $interfaceToCall = InterfaceToCall::create($interfaceName, $methodName);
        if ($interfaceToCall->doesItNotReturnValue() && $replySender->hasReply()) {
            throw InvalidArgumentException::create("Can't create gateway with reply channel, when {$interfaceToCall} is void");
        }
        if (!$interfaceToCall->doesItNotReturnValue() && !$replySender->hasReply()) {
            throw InvalidArgumentException::create("Interface {$interfaceToCall} has return value, but no reply channel was defined");
        }
    }
}