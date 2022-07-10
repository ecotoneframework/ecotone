<?php

namespace Test\Ecotone\Modelling\Unit\MessageHandling;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\StubMethodInvocation;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagator;
use PHPUnit\Framework\TestCase;

class MessageHeadersPropagatorTest extends TestCase
{
    public function test_after_calling_dropping_last_headers()
    {
        $messageHeadersPropagator = $this->getMessageHeadersPropagator();

        $messageHeadersPropagator->storeHeaders(
            StubMethodInvocation::createEndingImmediately(),
            MessageBuilder::withPayload("some")
                ->setHeader("token", 123)
                ->build()
        );

        $this->assertEquals(
            [],
            $messageHeadersPropagator->getLastHeaders()
        );
    }

    public function test_returning_no_headers_when_called_for_first_time()
    {
        $messageHeadersPropagator = $this->getMessageHeadersPropagator();

        $this->assertEquals(
            [],
            $messageHeadersPropagator->getLastHeaders()
        );
    }

    private function getMessageHeadersPropagator(): MessageHeadersPropagator
    {
        return new MessageHeadersPropagator();
    }
}