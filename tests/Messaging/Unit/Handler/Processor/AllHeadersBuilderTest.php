<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\Container\BoundParameterConverter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\HeadersConversionService;

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
        $result = ComponentTestBuilder::create()
            ->build(new BoundParameterConverter(
                AllHeadersBuilder::createWith('some'),
                InterfaceToCall::create(HeadersConversionService::class, 'withNullableString'),
            ))->getArgumentFrom(
                MessageBuilder::withPayload('some')
                ->setHeader('someId', 123)
                ->build(),
            );
        unset($result[MessageHeaders::MESSAGE_ID]);
        unset($result[MessageHeaders::MESSAGE_CORRELATION_ID]);
        unset($result[MessageHeaders::TIMESTAMP]);

        $this->assertEquals(
            [
                'someId' => 123,
            ],
            $result
        );
    }
}
