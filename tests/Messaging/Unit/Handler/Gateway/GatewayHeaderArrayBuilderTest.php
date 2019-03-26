<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Gateway;

use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderArrayBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderArrayConverter;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class GatewayHeaderArrayBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Gateway
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeaderArrayBuilderTest extends MessagingTest
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_evaluating_gateway_parameter()
    {
        $converter = GatewayHeaderArrayBuilder::create("test")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            MessageBuilder::withPayload("some")
                ->setHeader("token", 123)
                ->setHeader("password", "some"),
            $converter->convertToMessage(
                \SimplyCodedSoftware\Messaging\Handler\MethodArgument::createWith(InterfaceParameter::createNullable("test", TypeDescriptor::createArrayType()), [
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
        $converter = GatewayHeaderArrayBuilder::create("test")
            ->build(InMemoryReferenceSearchService::createEmpty());

        $this->expectException(InvalidArgumentException::class);

        $converter->convertToMessage(
            \SimplyCodedSoftware\Messaging\Handler\MethodArgument::createWith(InterfaceParameter::createNullable("test", TypeDescriptor::createStringType()), "sine"),
            MessageBuilder::withPayload("some")
        );
    }
}