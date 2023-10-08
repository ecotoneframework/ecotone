<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Transformer;

use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
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
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class TransformerBuilderTest extends MessagingTest
{
    public function test_passing_message_to_transforming_class_if_there_is_type_hint_for_it()
    {
        $payload = 'some';
        $outputChannel = QueueChannel::create();
        $outputChannelName = 'output';
        $objectToInvoke = 'objecToInvoke';
        $transformer = TransformerBuilder::create($objectToInvoke, InterfaceToCall::create(ServiceExpectingMessageAndReturningMessage::class, 'send'))
                            ->withOutputMessageChannel($outputChannelName)
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    $outputChannelName => $outputChannel,
                                ]),
                                InMemoryReferenceSearchService::createWith([
                                    $objectToInvoke => ServiceExpectingMessageAndReturningMessage::create($payload),
                                ])
                            );

        $message = MessageBuilder::withPayload('some123')->build();
        $transformer->handle($message);

        $this->assertMessages(
            MessageBuilder::fromMessage($message)
                ->setPayload($payload)
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_passing_message_payload_as_default()
    {
        $payload = 'someBigPayload';
        $outputChannel = QueueChannel::create();
        $outputChannelName = 'output';
        $objectToInvokeReference = 'service-a';
        $transformer = TransformerBuilder::create($objectToInvokeReference, InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withReturnValue'))
                            ->withOutputMessageChannel($outputChannelName)
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    $outputChannelName => $outputChannel,
                                ]),
                                InMemoryReferenceSearchService::createWith([
                                    $objectToInvokeReference => ServiceExpectingOneArgument::create(),
                                ])
                            );

        $message = MessageBuilder::withPayload($payload)->build();
        $transformer->handle($message);

        $this->assertMessages(
            MessageBuilder::fromMessage($message)
                ->setPayload($payload)
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::STRING))
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_throwing_exception_if_void_method_provided_for_transformation()
    {
        $this->expectException(InvalidArgumentException::class);

        $outputChannelName = 'outputChannelName';
        TransformerBuilder::createWithDirectObject(ServiceWithoutReturnValue::create(), 'setName')
                            ->withOutputMessageChannel($outputChannelName)
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    $outputChannelName => QueueChannel::create(),
                                ]),
                                InMemoryReferenceSearchService::createEmpty()
                            );
    }

    public function test_not_sending_message_to_output_channel_if_transforming_method_returns_null()
    {
        $outputChannel = QueueChannel::create();
        $outputChannelName = 'output';
        $objectToInvokeReference = 'service-a';
        $transformer = TransformerBuilder::create($objectToInvokeReference, InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withNullReturnValue'))
                        ->withOutputMessageChannel($outputChannelName)
                        ->build(
                            InMemoryChannelResolver::createFromAssociativeArray([
                                $outputChannelName => $outputChannel,
                            ]),
                            InMemoryReferenceSearchService::createWith([
                                $objectToInvokeReference => ServiceExpectingOneArgument::create(),
                            ])
                        );

        $transformer->handle(MessageBuilder::withPayload('some')->build());

        $this->assertNull($outputChannel->receive());
    }

    public function test_transforming_headers_if_array_returned_by_transforming_method()
    {
        $payload = 'someBigPayload';
        $outputChannel = QueueChannel::create();
        $inputChannelName = 'input';
        $outputChannelName = 'output';
        $transformer = TransformerBuilder::createWithDirectObject(ServiceExpectingOneArgument::create(), 'withArrayReturnValue')
                            ->withOutputMessageChannel($outputChannelName)
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    $inputChannelName => DirectChannel::create(),
                                    $outputChannelName => $outputChannel,
                                ]),
                                InMemoryReferenceSearchService::createEmpty()
                            );

        $message = MessageBuilder::withPayload($payload)
            ->setContentType(MediaType::createApplicationXPHP())
            ->build();
        $transformer->handle($message);

        $this->assertMessages(
            MessageBuilder::fromMessage($message)
                ->setPayload($payload)
                ->setHeader('some', $payload)
                ->setContentType(MediaType::createApplicationXPHP())
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_transforming_headers_if_array_returned_and_message_payload_is_also_array()
    {
        $payload = ['some' => 'some payload'];
        $outputChannel = QueueChannel::create();
        $outputChannelName = 'output';
        $transformer = TransformerBuilder::createWithDirectObject(ServiceExpectingOneArgument::create(), 'withArrayTypeHintAndArrayReturnValue')
                        ->withOutputMessageChannel($outputChannelName)
                        ->build(
                            InMemoryChannelResolver::createFromAssociativeArray([
                                $outputChannelName => $outputChannel,
                            ]),
                            InMemoryReferenceSearchService::createEmpty()
                        );

        $message = MessageBuilder::withPayload($payload)->build();
        $transformer->handle($message);

        $this->assertMessages(
            MessageBuilder::fromMessage($message)
                ->setPayload($payload)
                ->setHeader('some', 'some payload')
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_transforming_with_custom_method_arguments_converters()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $outputChannel = QueueChannel::create();
        $outputChannelName = 'output';
        $transformerBuilder = TransformerBuilder::createWithDirectObject(ServiceExpectingTwoArguments::create(), 'withReturnValue')
                                ->withOutputMessageChannel($outputChannelName);
        $transformerBuilder->withMethodParameterConverters([
            PayloadBuilder::create('name'),
            HeaderBuilder::create('surname', 'token'),
        ]);
        $transformer = $transformerBuilder
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $outputChannelName => $outputChannel,
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload($payload)
            ->setHeader('token', $headerValue)
            ->build();
        $transformer->handle($message);

        $this->assertMessages(
            MessageBuilder::fromMessage($message)
                ->setPayload($payload . $headerValue)
                ->setHeader('token', $headerValue)
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::STRING))
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_transforming_with_header_enricher()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $outputChannel = QueueChannel::create();
        $inputChannelName = 'input';
        $outputChannelName = 'output';
        $transformer = TransformerBuilder::createHeaderEnricher([
            'token' => $headerValue,
            'correlation-id' => 1,
        ])
            ->withOutputMessageChannel($outputChannelName)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => DirectChannel::create(),
                    $outputChannelName => $outputChannel,
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload($payload)->build();
        $transformer->handle($message);

        $this->assertMessages(
            MessageBuilder::fromMessage($message)
                ->setPayload($payload)
                ->setHeader('token', $headerValue)
                ->setHeader('correlation-id', 1)
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_transforming_with_header_mapper()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $outputChannel = QueueChannel::create();
        $inputChannelName = 'input';
        $outputChannelName = 'output';
        $transformer = TransformerBuilder::createHeaderMapper([
            'token' => 'secret',
        ])
            ->withOutputMessageChannel($outputChannelName)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => DirectChannel::create(),
                    $outputChannelName => $outputChannel,
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload($payload)
            ->setHeader('token', $headerValue)
            ->build();
        $transformer->handle($message);

        $this->assertMessages(
            MessageBuilder::fromMessage($message)
                ->setPayload($payload)
                ->setHeader('token', $headerValue)
                ->setHeader('secret', $headerValue)
                ->build(),
            $outputChannel->receive()
        );
    }

    public function test_transforming_with_transformer_instance_of_object()
    {
        $referenceObject = ServiceWithReturnValue::create();

        $transformer = TransformerBuilder::createWithDirectObject($referenceObject, 'getName')
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );

        $replyChannel = QueueChannel::create();
        $message = MessageBuilder::withPayload('some')->setReplyChannel($replyChannel)->build();
        $transformer->handle($message);

        $this->assertMessages(
            MessageBuilder::fromMessage($message)
                ->setPayload('johny')
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::STRING))
                ->setReplyChannel($replyChannel)
                ->build(),
            $replyChannel->receive()
        );
    }

    public function test_transforming_payload_using_expression()
    {
        $payload = 13;
        $outputChannel = QueueChannel::create();

        $transformer = TransformerBuilder::createWithExpression('payload + 3')
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createWith([
                    ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create(),
                ])
            );

        $transformer->handle(
            MessageBuilder::withPayload($payload)
                ->setReplyChannel($outputChannel)
                ->build()
        );

        $this->assertEquals(
            16,
            $outputChannel->receive()->getPayload()
        );
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
        $objectToInvoke = CalculatingService::create(0);
        $replyChannel = QueueChannel::create();

        $serviceActivator = TransformerBuilder::createWithDirectObject($objectToInvoke, 'result')
            ->withInputChannelName('someName')
            ->withEndpointId('someEndpoint')
            ->addAroundInterceptor(AroundInterceptorReference::create(CalculatingServiceInterceptorExample::class, InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'sum'), 2, '', []))
            ->addAroundInterceptor(AroundInterceptorReference::create(CalculatingServiceInterceptorExample::class, InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'multiply'), 1, '', []))
            ->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
                CalculatingServiceInterceptorExample::class => CalculatingServiceInterceptorExample::create(4),
            ]));

        $serviceActivator->handle(MessageBuilder::withPayload(2)->setReplyChannel($replyChannel)->build());

        $this->assertEquals(
            24,
            $replyChannel->receive()->getPayload()
        );
    }
}
