<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Endpoint;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;

/**
 * Class InboundChannelAdapterStoppingService
 * @package Test\Ecotone\Messaging\Fixture\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerStoppingService
{
    /**
     * @var ConsumerLifecycle
     */
    private $consumerLifecycle;

    /**
     * @var mixed
     */
    private $returnValue;
    /**
     * @var mixed
     */
    private $receivedPayload;

    /**
     * InboundChannelAdapterStoppingService constructor.
     * @param $returnValue
     */
    private function __construct($returnValue)
    {
        $this->returnValue = $returnValue;
    }

    /**
     * @param $returnValue
     * @return ConsumerStoppingService
     */
    public static function create($returnValue) : self
    {
        return new self($returnValue);
    }

    public function execute()
    {
        $this->consumerLifecycle->stop();

        return $this->returnValue;
    }

    public function executeNoReturn($receivedPayload) : void
    {
        $this->receivedPayload = $receivedPayload;
        $this->consumerLifecycle->stop();
    }

    /**
     * @param ConsumerLifecycle $consumerLifecycle
     */
    public function setConsumerLifecycle(ConsumerLifecycle $consumerLifecycle) : void
    {
        $this->consumerLifecycle = $consumerLifecycle;
    }

    /**
     * @return mixed
     */
    public function getReceivedPayload()
    {
        return $this->receivedPayload;
    }
}