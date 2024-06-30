<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;

/**
 * Class AllHeadersBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Processor
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class AllHeadersBuilderTest extends TestCase
{
    public function test_retrieving_all_headers()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([AllHeadersBuilder::createWith('value')])
            )
            ->build();

        $headers = [
            MessageHeaders::MESSAGE_ID => '123',
            MessageHeaders::MESSAGE_CORRELATION_ID => '123',
            MessageHeaders::TIMESTAMP => '123',
            'some' => 'test',
        ];

        $evaluatedParameter = $messaging->sendDirectToChannel($inputChannel, 'test', metadata: $headers);

        foreach ($headers as $headerName => $headerValue) {
            $this->assertEquals($headerValue, $evaluatedParameter[$headerName]);
        }
    }
}
