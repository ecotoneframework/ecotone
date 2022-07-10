<?php


namespace Test\Ecotone\Messaging\Fixture\Endpoint;

use Ecotone\Messaging\Transaction\Transactional;

#[Transactional(["transactionFactory1"])]
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

    #[Transactional(["transactionFactory2"])]
    public function executeReturnWithInterceptor()
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