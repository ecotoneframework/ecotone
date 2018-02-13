<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Rabbitmq;

/**
 * Class RabbitSendConfiguration
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RabbitSendConfiguration
{
    private const RETURN_CORRELATION_KEY = "messaging_request_return_correlation";
    private const DEFAULT_EXCHANGE = "";
    private const DEFAULT_ROUTING_KEY = "";
    private const DEFAULT_ENCODING = "UTF-8";

    /**
     * @var string
     */
    private $exchangeName;
    /**
     * @var string
     */
    private $routingKey;
    /**
     * @var RabbitMessageConverter
     */
    private $messageConverter;

    private function __construct()
    {
    }

    /**
     * @return RabbitSendConfiguration
     */
    public static function createEmpty() : self
    {
        return new self();
    }

    /**
     * @return RabbitSendConfiguration
     */
    public static function createWithDefaults() : self
    {
        $configuration = self::createEmpty();

        $configuration->exchangeName = self::DEFAULT_EXCHANGE;
        $configuration->routingKey = self::DEFAULT_ROUTING_KEY;
    }

}