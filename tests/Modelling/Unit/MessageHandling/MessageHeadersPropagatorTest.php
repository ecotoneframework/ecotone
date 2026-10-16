<?php

namespace Test\Ecotone\Modelling\Unit\MessageHandling;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\StubMethodInvocation;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
use Ecotone\Test\StaticMessageBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class MessageHeadersPropagatorTest extends TestCase
{
    public function test_after_calling_dropping_last_headers()
    {
        $messageHeadersPropagator = $this->getMessageHeadersPropagator();

        $messageHeadersPropagator->storeHeaders(
            StubMethodInvocation::createEndingImmediately(),
            StaticMessageBuilder::withPayload('some')
                ->setHeader('token', 123)
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

    private function getMessageHeadersPropagator(): MessageHeadersPropagatorInterceptor
    {
        return new MessageHeadersPropagatorInterceptor();
    }
}
