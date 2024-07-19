<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Transformer;

use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ComponentTestBuilder;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingMessageAndReturningMessage;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingTwoArguments;
use Test\Ecotone\Messaging\Fixture\Service\ServiceWithoutReturnValue;
use Test\Ecotone\Messaging\Fixture\Service\ServiceWithReturnValue;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class TransformerBuilder
 * @package Ecotone\Messaging\Handler\Transformer
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class TransformerBuilderTest extends MessagingTest
{
    public function test_modifying_payload()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference($referenceName = 'reference', ServiceExpectingMessageAndReturningMessage::create($payload = 'some'))
            ->withMessageHandler(
                TransformerBuilder::create($referenceName, InterfaceToCall::create(ServiceExpectingMessageAndReturningMessage::class, 'send'))
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $this->assertEquals(
            $payload,
            $messaging->sendDirectToChannel('inputChannel', 'some123')
        );
    }

    public function test_transforming_message_payload_as_default()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference($referenceName = 'reference', ServiceExpectingOneArgument::create())
            ->withMessageHandler(
                TransformerBuilder::create($referenceName, InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withReturnMixed'))
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $this->assertEquals(
            new stdClass(),
            $messaging->sendDirectToChannelWithMessageReply('inputChannel', new stdClass())->getPayload()
        );
    }

    public function test_throwing_exception_if_void_method_provided_for_transformation()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createWithDirectObject(ServiceWithoutReturnValue::create(), 'setName')
                    ->withInputChannelName('inputChannel')
            )
            ->build();
    }

    public function test_not_sending_message_to_output_channel_if_transforming_method_returns_null()
    {
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($outputChannel = 'outputChannel'))
            ->withReference($referenceName = 'reference', ServiceExpectingOneArgument::create())
            ->withMessageHandler(
                TransformerBuilder::create($referenceName, InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withNullReturnValue'))
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withOutputMessageChannel($outputChannel)
            )
            ->build();

        $messaging->sendDirectToChannel($inputChannel, 'test');

        $this->assertNull($messaging->receiveMessageFrom($outputChannel));
    }

    public function test_transforming_headers_if_array_returned_by_transforming_method()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createWithDirectObject(ServiceExpectingOneArgument::create(), 'withArrayReturnValue')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply($inputChannel, 'test');

        $this->assertEquals(
            'test',
            $message->getHeaders()->get('some')
        );
    }

    public function test_transforming_headers_if_array_returned_and_message_payload_is_also_array()
    {
        $payload = ['some' => 'some payload'];
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createWithDirectObject(ServiceExpectingOneArgument::create(), 'withArrayTypeHintAndArrayReturnValue')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply($inputChannel, $payload);

        $this->assertEquals(
            'some payload',
            $message->getHeaders()->get('some')
        );
    }

    public function test_transforming_with_custom_method_arguments_converters()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createWithDirectObject(ServiceExpectingTwoArguments::create(), 'withReturnValue')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        PayloadBuilder::create('name'),
                        HeaderBuilder::create('surname', 'token'),
                    ])
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply($inputChannel, $payload, metadata:  ['token' => $headerValue]);

        $this->assertEquals(
            $payload . $headerValue,
            $message->getPayload()
        );

        $this->assertEquals(
            $headerValue,
            $message->getHeaders()->get('token')
        );
    }

    public function test_transforming_with_header_enricher()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createHeaderEnricher([
                    'token' => $headerValue,
                    'correlation-id' => 1,
                ])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply($inputChannel, $payload);

        $this->assertEquals($payload, $message->getPayload());
        $this->assertEquals($headerValue, $message->getHeaders()->get('token'));
        $this->assertEquals(1, $message->getHeaders()->get('correlation-id'));
    }

    public function test_transforming_with_header_mapper()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createHeaderMapper([
                    'token' => 'secret',
                ])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply($inputChannel, $payload, metadata: ['token' => $headerValue]);

        $this->assertEquals($payload, $message->getPayload());
        $this->assertEquals($headerValue, $message->getHeaders()->get('token'));
        $this->assertEquals($headerValue, $message->getHeaders()->get('secret'));
    }

    public function test_transforming_with_transformer_instance_of_object()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createWithDirectObject(ServiceWithReturnValue::create(), 'getName')
                    ->withInputChannelName('input')
            )
            ->build();

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::STRING),
            $messaging->sendDirectToChannelWithMessageReply('input', 'johny')->getHeaders()->getContentType()
        );
    }

    public function test_transforming_payload_using_expression()
    {
        $payload = 1;
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                TransformerBuilder::createWithExpression('payload + 3')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply($inputChannel, $payload);

        $this->assertEquals($payload + 3, $message->getPayload());
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = 'someName';

        $this->assertIsString(
            (string)TransformerBuilder::create('ref-name', InterfaceToCall::create(CalculatingService::class, 'result'))
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName)
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_creating_with_interceptors()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference(CalculatingServiceInterceptorExample::class, CalculatingServiceInterceptorExample::create(4))
            ->withMessageHandler(
                TransformerBuilder::createWithDirectObject(CalculatingService::create(0), 'result')
                    ->withEndpointId('someEndpoint')
                    ->addAroundInterceptor(AroundInterceptorBuilder::create(CalculatingServiceInterceptorExample::class, InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'sum'), 2, '', []))
                    ->addAroundInterceptor(AroundInterceptorBuilder::create(CalculatingServiceInterceptorExample::class, InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'multiply'), 1, '', []))
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            20,
            $messaging->sendDirectToChannel($inputChannel, 1)
        );
    }
}
