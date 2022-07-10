<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersConverter;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class GatewayHeaderArrayBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Gateway
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeaderArrayBuilderTest extends MessagingTest
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_evaluating_gateway_parameter()
    {
        $converter = GatewayHeadersBuilder::create("test")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            MessageBuilder::withPayload("some")
                ->setHeader("token", 123)
                ->setHeader("password", "some"),
            $converter->convertToMessage(
                \Ecotone\Messaging\Handler\MethodArgument::createWith(InterfaceParameter::createNullable("test", TypeDescriptor::createArrayType()), [
                    "token" => 123,
                    "password" => "some",
                    "rabbit" => null
                ]),
                MessageBuilder::withPayload("some")
            )
        );
    }

    public function test_throwing_exception_if_passed_argument_is_not_array()
    {
        $converter = GatewayHeadersBuilder::create("test")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->expectException(InvalidArgumentException::class);

        $converter->convertToMessage(
            \Ecotone\Messaging\Handler\MethodArgument::createWith(InterfaceParameter::createNullable("test", TypeDescriptor::createStringType()), "sine"),
            MessageBuilder::withPayload("some")
        );
    }
}