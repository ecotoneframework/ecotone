<?php

namespace Messaging\Handler\Gateway;

/**
 * Class GatewayProxy
 * @package Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayProxy
{
    /**
     * @var MethodCallToMessageConverter
     */
    private $methodCallToMessageConverter;
    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * GatewayProxy constructor.
     * @param MethodCallToMessageConverter $methodCallToMessageConverter
     * @param Gateway $gateway
     */
    private function __construct(MethodCallToMessageConverter $methodCallToMessageConverter, Gateway $gateway)
    {
        $this->methodCallToMessageConverter = $methodCallToMessageConverter;
        $this->gateway = $gateway;
    }

    public static function create() : self
    {

    }

    /**
     * @param string $className
     * @param string $methodName
     * @param array|mixed[] $methodArgumentValues
     */
    public function execute(string $className, string $methodName, array $methodArgumentValues) : void
    {
        $methodArguments = [];
        $reflectionMethod = new \ReflectionMethod($className, $methodName);

        $parameters = $reflectionMethod->getParameters();
        $countArguments = count($methodArgumentValues);
        for ($index = 0; $index < $countArguments; $index++) {
            $methodArguments[] = MethodArgument::createWith($parameters[$index], $methodArgumentValues[$index]);
        }

        $message = $this->methodCallToMessageConverter->convertFor($methodArguments);
        $this->gateway->handle($message);
    }
}