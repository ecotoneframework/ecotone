<?php

namespace SimplyCodedSoftware\Messaging\Rabbitmq;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Class RabbitTemplate
 * @package SimplyCodedSoftware\Messaging\Rabbitmq
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RabbitTemplate
{
    private const DEFAULT_REPLY_TIMEOUT = 5000;
    private const DEFAULT_CONSUME_TIMEOUT = 10000;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var RabbitSendConfiguration
     */
    private $sendConfiguration;


    /**
     * RabbitTemplate constructor.
     * @param ConnectionFactory $connectionFactory
     * @param RabbitSendConfiguration $sendConfiguration
     */
    private function __construct(ConnectionFactory $connectionFactory, RabbitSendConfiguration $sendConfiguration)
    {
        $this->connectionFactory = $connectionFactory;
        $this->sendConfiguration = $sendConfiguration;
    }

    /**
     * @param ConnectionFactory $connectionFactory
     * @return RabbitTemplate
     */
    public static function createWith(ConnectionFactory $connectionFactory) : self
    {
        return new self($connectionFactory, RabbitSendConfiguration::createWithDefaults());
    }

    /**
     * @param ConnectionFactory $connectionFactory
     * @param RabbitSendConfiguration $sendConfiguration
     * @return RabbitTemplate
     */
    public static function createWithConfiguration(ConnectionFactory $connectionFactory, RabbitSendConfiguration $sendConfiguration) : self
    {
        return new self($connectionFactory, $sendConfiguration);
    }

//    public function send(Me1ssage $message)


}