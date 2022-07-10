<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Endpoint\ConsumerLifecycle;

/**
 * Class ServiceTurningOffConsumer
 * @package Test\Ecotone\Messaging\Fixture\Service
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