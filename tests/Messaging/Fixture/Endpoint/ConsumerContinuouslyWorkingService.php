<?php


namespace Test\SimplyCodedSoftware\Messaging\Fixture\Endpoint;

/**
 * Class ConsumerContinuouslyWorkingService
 * @package Fixture\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerContinuouslyWorkingService
{
    private $receivedPayload;

    private $returnData;

    private function __construct($returnData)
    {
        $this->returnData = $returnData;
    }

    public static function create() : self
    {
        return new self(null);
    }

    public static function createWithReturn($returnData)
    {
        return new self($returnData);
    }


    public function executeReturn()
    {
        return $this->returnData;
    }

    public function executeNoReturn($receivedPayload): void
    {
        $this->receivedPayload = $receivedPayload;
    }

    /**
     * @return mixed
     */
    public function getReceivedPayload()
    {
        return $this->receivedPayload;
    }
}