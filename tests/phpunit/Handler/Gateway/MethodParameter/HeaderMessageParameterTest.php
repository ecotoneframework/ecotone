<?php

namespace Test\SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter;

use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\Gateway\MethodParameterConverter\HeaderMessageParameterToMessageConverter;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class HeaderMessageParameterTest
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderMessageParameterTest extends MessagingTest
{
    public function test_returning_parameter_name()
    {
        $parameterName = 'johny';
        $headerMessageParameter = HeaderMessageParameterToMessageConverter::create($parameterName, 'personName');

        $this->assertEquals($headerMessageParameter->parameterName(), $parameterName);
    }

    public function test_adding_value_to_message_header()
    {
        $headerName = 'personName';
        $headerMessageParameter = HeaderMessageParameterToMessageConverter::create('johny', $headerName);

        $messageBuilder = MessageBuilder::withPayload('test');
        $headerValue = 'JohnyMacaraony';

        $this->assertMessages(
            MessageBuilder::withPayload('test')
                ->setHeader($headerName, $headerValue)
                ->build(),
            $headerMessageParameter->convertToMessage(MethodArgument::createWith($headerName, $headerValue), $messageBuilder)
                ->build()
        );
    }
}