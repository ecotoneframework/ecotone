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

    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }


    public function executeReturn() : \stdClass
    {
        return new \stdClass();
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