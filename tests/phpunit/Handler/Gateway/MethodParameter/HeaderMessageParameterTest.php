<?php

namespace Messaging\Handler\Gateway\MethodParameterConverter;

use Messaging\Handler\Gateway\MethodArgument;
use Messaging\MessagingTest;
use Messaging\Support\MessageBuilder;

/**
 * Class HeaderMessageParameterTest
 * @package Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderMessageParameterTest extends MessagingTest
{
    public function test_returning_parameter_name()
    {
        $parameterName = 'johny';
        $headerMessageParameter = HeaderMessageArgumentConverter::create($parameterName, 'personName');

        $this->assertEquals($headerMessageParameter->parameterName(), $parameterName);
    }

    public function test_adding_value_to_message_header()
    {
        $headerName = 'personName';
        $headerMessageParameter = HeaderMessageArgumentConverter::create('johny', $headerName);

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