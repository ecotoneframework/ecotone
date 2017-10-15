<?php

namespace Messaging\Handler\Processor;

use Fixture\Service\ServiceWithoutReturnValue;
use PHPUnit\Framework\TestCase;

/**
 * Class MethodInvokingMessageProcessorTest
 * @package Messaging\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInvokingMessageProcessorTest extends TestCase
{
    public function test_returning_null_when_calling_service_with_no_return_value()
    {
        $service = ServiceWithoutReturnValue::create();

//        $this->
    }
}