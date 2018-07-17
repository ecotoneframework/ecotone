<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class MethodCallToMessageConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodCallToMessageConverter
{
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var array|GatewayParameterConverter[]
     */
    private $methodArgumentConverters;

    /**
     * MethodCallToMessageConverter constructor.
     * @param string $interfaceToCall
     * @param string $methodName
     * @param array|GatewayParameterConverter[] $methodArgumentConverters
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function __construct(string $interfaceToCall, string $methodName, array $methodArgumentConverters)
    {
        $this->initialize($interfaceToCall, $methodName, $methodArgumentConverters);
    }

    /**
     * @param array|MethodArgument[] $methodArguments
     * @return MessageBuilder
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function convertFor(array $methodArguments) : MessageBuilder
    {
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);
        $messageBuilder = MessageBuilder::withPayload("empty");

        foreach ($methodArguments as $methodArgument) {
            foreach ($this->methodArgumentConverters as $methodParameterConverter) {
                if ($methodParameterConverter->isSupporting($methodArgument)) {
                    $messageBuilder = $methodParameterConverter->convertToMessage($methodArgument, $messageBuilder);
                }
            }
        }

        return $messageBuilder;
    }

    /**
     * @param string $interfaceToCall
     * @param string $methodName
     * @param array|GatewayParameterConverter[] $methodArgumentConverters
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $interfaceToCall, string $methodName, array $methodArgumentConverters) : void
    {
        Assert::allInstanceOfType($methodArgumentConverters, GatewayParameterConverter::class);

        $this->interfaceToCall = InterfaceToCall::create($interfaceToCall, $methodName);

        if (empty($methodArgumentConverters) && $this->interfaceToCall->hasMoreThanOneParameter()) {
            throw InvalidArgumentException::create("You need to pass method argument converts for {$this->interfaceToCall}");
        }

        if (empty($methodArgumentConverters) && $this->interfaceToCall->hasSingleArgument()) {
            $methodArgumentConverters = [GatewayPayloadBuilder::create($this->interfaceToCall->getFirstParameterName())->build()];
        }

        $this->methodArgumentConverters = $methodArgumentConverters;
    }
}