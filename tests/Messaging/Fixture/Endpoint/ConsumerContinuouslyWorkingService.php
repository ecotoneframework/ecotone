<?php

namespace Test\Ecotone\Messaging\Fixture\Endpoint;

use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\RecordOutcomeIn;

#[RecordOutcomeIn('classRecorder')]
/**
 * licence Apache-2.0
 */
class ConsumerContinuouslyWorkingService
{
    private $receivedPayload;

    private $returnData;

    private function __construct($returnData)
    {
        $this->returnData = $returnData;
    }

    public static function create(): self
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

    #[RecordOutcomeIn('methodRecorder')]
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
