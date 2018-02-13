<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

use Fixture\Service\ServiceInterface\ServiceInterfaceReceiveOnly;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendOnlyWithThreeArguments;
use Fixture\Service\ServiceInterface\ServiceInterfaceSendOnlyWithTwoArguments;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\MethodCallToMessageConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToHeaderConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\ParameterToPayloadConverter;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class MethodCallToMessageConverterTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodCallToMessageConverterTest extends MessagingTest
{
    public function TODO_test_converting_to_message_with_payload()
    {
        $parameterName = 'content';
        $methodCallToMessageConverter = new MethodCallToMessageConverter(
            ServiceInterfaceSendOnly::class, 'sendMail',
            [ParameterToPayloadConverter::create($parameterName)]
        );

        $argumentValue = "Hello Johny";
        $this->assertMessages(
            MessageBuilder::withPayload($argumentValue)
                ->build(),
            $methodCallToMessageConverter->convertFor([
                MethodArgument::createWith($parameterName, $argumentValue)
            ])->build()
        );
    }

    public function TODO_test_converting_to_message_with_additional_header()
    {
        $payloadParameterName = 'content';
        $payloadArgumentValue = "Hello Johny";
        $personIdName = "personId";
        $personIdValue = 123;
        $methodCallToMessageConverter = new MethodCallToMessageConverter(
            ServiceInterfaceSendOnlyWithTwoArguments::class, 'sendMail',
            [
                ParameterToPayloadConverter::create($payloadParameterName),
                ParameterToHeaderConverter::create(
                    $personIdName, $personIdName
                )
            ]
        );

        $this->assertMessages(
            MessageBuilder::withPayload($payloadArgumentValue)
                ->setHeader($personIdName, $personIdValue)
                ->build(),
            $methodCallToMessageConverter->convertFor([
                MethodArgument::createWith($payloadParameterName, $payloadArgumentValue),
                MethodArgument::createWith($personIdName, $personIdValue)
            ])->build()
        );
    }

    public function TODO_test_converting_with_multiple_not_ordered_parameter_converters()
    {
        $numberParameterName = "number";
        $numberArgumentValue = 1000;

        $multiplyParameterName = "multiplyBy";
        $multiplyHeaderName = "multiply";
        $multiplyValue = 10;

        $percentageParameterName = "percentage";
        $percentageHeaderName = "percentageAmount";
        $percentageValue = 0.51;

        $methodCallToMessageConverter = new MethodCallToMessageConverter(
            ServiceInterfaceSendOnlyWithThreeArguments::class, 'calculate',
            [
                ParameterToPayloadConverter::create($numberParameterName),
                ParameterToHeaderConverter::create(
                    $multiplyParameterName, $multiplyHeaderName
                ),
                ParameterToHeaderConverter::create(
                    $percentageParameterName, $percentageHeaderName
                )
            ]
        );

        $this->assertMessages(
            MessageBuilder::withPayload($numberArgumentValue)
                ->setHeader($multiplyHeaderName, $multiplyValue)
                ->setHeader($percentageHeaderName, $percentageValue)
                ->build(),
            $methodCallToMessageConverter->convertFor([
                MethodArgument::createWith($numberParameterName, $numberArgumentValue),
                MethodArgument::createWith($multiplyParameterName, $multiplyValue),
                MethodArgument::createWith($percentageParameterName, $percentageValue)
            ])
                ->build()
        );
    }

    public function test_throwing_exception_if_interface_has_no_defined_method()
    {
        $this->expectException(InvalidArgumentException::class);

        new MethodCallToMessageConverter(
            ServiceInterfaceSendOnly::class, 'wrongMethodName',
            [ParameterToPayloadConverter::create('some')]
        );
    }

    public function TODO_test_if_no_converters_defined_pick_payload_as_default_for_one_argument_method()
    {
        $contentParameterName = "content";
        $contentArgumentValue = "Hello mr johny";

        $methodCallToMessageConverter = new MethodCallToMessageConverter(
            ServiceInterfaceSendOnly::class, 'sendMail', []
        );

        $this->assertMessages(
            MessageBuilder::withPayload($contentArgumentValue)
                ->build(),
            $methodCallToMessageConverter->convertFor([
                MethodArgument::createWith(new \ReflectionParameter(function(string $content) {}, $contentParameterName), $contentArgumentValue)
            ])
                ->build()
        );
    }

    public function test_throwing_exception_if_no_converters_passed_and_method_has_multiple_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        new MethodCallToMessageConverter(
            ServiceInterfaceSendOnlyWithTwoArguments::class, 'sendMail', []
        );
    }

    public function test_creating_empty_default_message_when_no_parameters_required_for_method()
    {
        $methodCallToMessageConverter = new MethodCallToMessageConverter(
            ServiceInterfaceReceiveOnly::class, 'sendMail', []
        );

        $this->assertMessages(
            MessageBuilder::withPayload('empty')
                ->build(),
            $methodCallToMessageConverter->convertFor([])
                ->build()
        );
    }
}