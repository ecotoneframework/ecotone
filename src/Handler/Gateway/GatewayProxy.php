<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
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
     * @var SendAndReceiveService
     */
    private $requestReplyService;

    /**
     * GatewayProxy constructor.
     * @param string $className
     * @param string $methodName
     * @param MethodCallToMessageConverter $methodCallToMessageConverter
     * @param SendAndReceiveService $requestReplyService
     */
    public function __construct(string $className, string $methodName, MethodCallToMessageConverter $methodCallToMessageConverter, SendAndReceiveService $requestReplyService)
    {
        $this->methodCallToMessageConverter = $methodCallToMessageConverter;
        $this->className = $className;
        $this->methodName = $methodName;
        $this->requestReplyService = $requestReplyService;
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
        $message = $this->requestReplyService
                        ->prepareForSend($message, $interfaceToCall)
                        ->build();

        $this->requestReplyService->send($message);

        if ($interfaceToCall->doesItReturnFuture()) {
            return FutureReplyReceiver::create($this->requestReplyService);
        }

        $replyMessage = $this->requestReplyService->receiveReply();
        if (is_null($replyMessage) && !$interfaceToCall->doesItNotReturnValue() && !$interfaceToCall->canItReturnNull()) {
            throw InvalidArgumentException::create("{$interfaceToCall} expects value, but null was returned. If you defined errorChannel it's advised to change interface to nullable.");
        }

        return $replyMessage ? $replyMessage->getPayload() : null;
    }
}