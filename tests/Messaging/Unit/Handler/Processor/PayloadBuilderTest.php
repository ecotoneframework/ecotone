<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\Container\BoundParameterConverter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use ReflectionException;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\HeadersConversionService;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class PayloadBuilder
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class PayloadBuilderTest extends MessagingTest
{
    /**
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_payload_converter()
    {
        $converter = PayloadBuilder::create('some');
        $converter = ComponentTestBuilder::create()
            ->build(new BoundParameterConverter(
                $converter,
                InterfaceToCall::create(HeadersConversionService::class, 'withNullableString')
            ));

        $payload = 'rabbit';
        $this->assertEquals(
            $payload,
            $converter->getArgumentFrom(
                MessageBuilder::withPayload($payload)->build(),
            )
        );
    }
}
