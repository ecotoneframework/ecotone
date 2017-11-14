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
    public function __construct(MethodCallToMessageConverter $methodCallToMessageConverter, Gateway $gateway)
    {
        $this->methodCallToMessageConverter = $methodCallToMessageConverter;
        $this->gateway = $gateway;
    }

    public function execute(array $methodArguments) : void
    {
//        $message = $this->methodCallToMessageConverter->convertFor()
    }
}