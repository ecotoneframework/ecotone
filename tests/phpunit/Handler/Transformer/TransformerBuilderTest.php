<?php

namespace Messaging\Handler\Transformer;

use Fixture\Service\ServiceExpectingMessageAndReturningMessage;
use Fixture\Service\ServiceExpectingOneArgument;
use Fixture\Service\ServiceExpectingTwoArguments;
use Fixture\Service\ServiceWithoutReturnValue;
use Messaging\Channel\DirectChannel;
use Messaging\Channel\QueueChannel;
use Messaging\Handler\Processor\MethodInvoker\HeaderArgument;
use Messaging\Handler\Processor\MethodInvoker\PayloadArgument;
use Messaging\MessagingTest;
use Messaging\Support\InvalidArgumentException;
use Messaging\Support\MessageBuilder;

/**
 * Class TransformerBuilder
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerBuilderTest extends MessagingTest
{
    public function test_passing_message_to_transforming_class_if_there_is_type_hint_for_it()
    {
        $payload = 'some';
        $outputChannel = QueueChannel::create();
        $transformer = TransformerBuilder::create(DirectChannel::create(), $outputChannel,ServiceExpectingMessageAndReturningMessage::create($payload), 'send', 'test')
                            ->build();

        $transformer->handle(MessageBuilder::withPayload('some123')->build());

        $this->assertMessages(
            MessageBuilder::withPayload($payload)
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_passing_message_payload_as_default()
    {
        $payload = 'someBigPayload';
        $outputChannel = QueueChannel::create();
        $transformer = TransformerBuilder::create(DirectChannel::create(), $outputChannel,ServiceExpectingOneArgument::create(), 'withReturnValue', 'test')
            ->build();

        $transformer->handle(MessageBuilder::withPayload($payload)->build());

        $this->assertMessages(
            MessageBuilder::withPayload($payload)
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_throwing_exception_if_void_method_provided_for_transformation()
    {
        $this->expectException(InvalidArgumentException::class);

        TransformerBuilder::create(DirectChannel::create(), QueueChannel::create(), ServiceWithoutReturnValue::create(), 'setName', 'test')
            ->build();
    }

    public function test_not_sending_message_to_output_channel_if_transforming_method_returns_null()
    {
        $outputChannel = QueueChannel::create();
        $transformer = TransformerBuilder::create(DirectChannel::create(), $outputChannel, ServiceExpectingOneArgument::create(), 'withNullReturnValue', 'test')
            ->build();

        $transformer->handle(MessageBuilder::withPayload('some')->build());

        $this->assertNull($outputChannel->receive());
    }

    public function test_transforming_headers_if_array_returned_by_transforming_method()
    {
        $payload = 'someBigPayload';
        $outputChannel = QueueChannel::create();
        $transformer = TransformerBuilder::create(DirectChannel::create(), $outputChannel,ServiceExpectingOneArgument::create(), 'withArrayReturnValue', 'test')
            ->build();

        $transformer->handle(MessageBuilder::withPayload($payload)->build());

        $this->assertMessages(
            MessageBuilder::withPayload($payload)
                ->setHeader('0', $payload)
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_transforming_payload_if_array_returned_and_message_payload_is_also_array()
    {
        $payload = ["some payload"];
        $outputChannel = QueueChannel::create();
        $transformer = TransformerBuilder::create(DirectChannel::create(), $outputChannel,ServiceExpectingOneArgument::create(), 'withArrayTypeHintAndArrayReturnValue', 'test')
            ->build();

        $transformer->handle(MessageBuilder::withPayload($payload)->build());

        $this->assertMessages(
            MessageBuilder::withPayload($payload)
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_transforming_with_custom_method_arguments_converters()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $outputChannel = QueueChannel::create();
        $transformer = TransformerBuilder::create(DirectChannel::create(), $outputChannel, ServiceExpectingTwoArguments::create(), 'withReturnValue', 'test')
            ->withMethodArguments([
                PayloadArgument::create('name'),
                HeaderArgument::create('surname', 'token')
            ])
            ->build();

        $transformer->handle(
            MessageBuilder::withPayload($payload)
                ->setHeader('token', $headerValue)
                ->build()
        );

        $this->assertMessages(
            MessageBuilder::withPayload($payload . $headerValue)
                ->setHeader('token', $headerValue)
                ->build(),
            $outputChannel->receive()
        );
    }
}