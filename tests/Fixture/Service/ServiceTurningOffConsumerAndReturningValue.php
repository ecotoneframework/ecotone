<?php
declare(strict_types=1);

namespace Fixture\Service;

use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerLifecycle;

/**
 * Class ServiceTurningOffConsumer
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceTurningOffConsumerAndReturningValue
{
    /**
     * @var mixed
     */
    private $data;
    /**
     * @var ConsumerLifecycle
     */
    private $consumer;

    /**
     * ServiceTurningOffConsumerAndReturningValue constructor.
     * @param $data
     */
    private function __construct($data)
    {
        $this->data = $data;
    }

    public static function create($data) : self
    {
        return new self($data);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $this->consumer->stop();

        return $this->data;
    }

    /**
     * @param ConsumerLifecycle $consumerLifecycle
     */
    public function setConsumer(ConsumerLifecycle $consumerLifecycle) : void
    {
        $this->consumer = $consumerLifecycle;
    }
}