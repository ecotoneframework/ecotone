<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;

use Fixture\Service\ServiceExpectingMessageAndReturningMessage;
use Fixture\Service\ServiceExpectingOneArgument;
use Fixture\Service\ServiceExpectingTwoArguments;
use Fixture\Service\ServiceWithoutReturnValue;
use Fixture\Service\ServiceWithReturnValue;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class TransformerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerBuilderTest extends MessagingTest
{
    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_passing_message_to_transforming_class_if_there_is_type_hint_for_it()
    {
        $payload = 'some';
        $outputChannel = QueueChannel::create();
        $outputChannelName = "output";
        $objectToInvoke = "objecToInvoke";
        $transformer = TransformerBuilder::create("someChannel", $objectToInvoke, 'send')
                            ->withOutputMessageChannel($outputChannelName)
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    $outputChannelName => $outputChannel
                                ]),
                                InMemoryReferenceSearchService::createWith([
                                    $objectToInvoke => ServiceExpectingMessageAndReturningMessage::create($payload)
                                ])
                            );

        $transformer->handle(MessageBuilder::withPayload('some123')->build());

        $this->assertMessages(
            MessageBuilder::withPayload($payload)
                ->build(),
            $outputChannel->receive()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_passing_message_payload_as_default()
    {
        $payload = 'someBigPayload';
        $outputChannel = QueueChannel::create();
        $outputChannelName = 'output';
        $objectToInvokeReference = "service-a";
        $transformer = TransformerBuilder::create("inputChannel", $objectToInvokeReference, 'withReturnValue')
                            ->withOutputMessageChannel($outputChannelName)
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    $outputChannelName => $outputChannel
                                ]),
                                InMemoryReferenceSearchService::createWith([
                                    $objectToInvokeReference => ServiceExpectingOneArgument::create()
                                ])
                            );

        $transformer->handle(MessageBuilder::withPayload($payload)->build());

        $this->assertMessages(
            MessageBuilder::withPayload($payload)
                ->build(),
            $outputChannel->receive()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_void_method_provided_for_transformation()
    {
        $this->expectException(InvalidArgumentException::class);

        $outputChannelName = "outputChannelName";
        $objectToInvokeReference = "service-a";
        TransformerBuilder::create("inputChannel", $objectToInvokeReference, 'setName')
                            ->withOutputMessageChannel($outputChannelName)
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    $outputChannelName => QueueChannel::create()
                                ]),
                                InMemoryReferenceSearchService::createWith([
                                    $objectToInvokeReference => ServiceWithoutReturnValue::create()
                                ])
                            );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_not_sending_message_to_output_channel_if_transforming_method_returns_null()
    {
        $outputChannel = QueueChannel::create();
        $outputChannelName = "output";
        $objectToInvokeReference = "service-a";
        $transformer = TransformerBuilder::create(DirectChannel::create(), $objectToInvokeReference, 'withNullReturnValue')
                        ->withOutputMessageChannel($outputChannelName)
                        ->build(
                            InMemoryChannelResolver::createFromAssociativeArray([
                                $outputChannelName => $outputChannel
                            ]),
                            InMemoryReferenceSearchService::createWith([
                                $objectToInvokeReference => ServiceExpectingOneArgument::create()
                            ])
                        );

        $transformer->handle(MessageBuilder::withPayload('some')->build());

        $this->assertNull($outputChannel->receive());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_transforming_headers_if_array_returned_by_transforming_method()
    {
        $payload = 'someBigPayload';
        $outputChannel = QueueChannel::create();
        $inputChannelName = "input";
        $outputChannelName = "output";
        $objectToInvokeReference = "service-a";
        $transformer = TransformerBuilder::create($inputChannelName, $objectToInvokeReference, 'withArrayReturnValue')
                            ->withOutputMessageChannel($outputChannelName)
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    $inputChannelName => DirectChannel::create(),
                                    $outputChannelName => $outputChannel
                                ]),
                                InMemoryReferenceSearchService::createWith([
                                    $objectToInvokeReference => ServiceExpectingOneArgument::create()
                                ])
                            );

        $transformer->handle(MessageBuilder::withPayload($payload)->build());

        $this->assertMessages(
            MessageBuilder::withPayload($payload)
                ->setHeader('0', $payload)
                ->build(),
            $outputChannel->receive()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_transforming_payload_if_array_returned_and_message_payload_is_also_array()
    {
        $payload = ["some payload"];
        $outputChannel = QueueChannel::create();
        $inputChannelName = "input";
        $outputChannelName = "output";
        $objectToInvokeReference = "service-a";
        $transformer = TransformerBuilder::create($inputChannelName, $objectToInvokeReference, 'withArrayTypeHintAndArrayReturnValue')
                        ->withOutputMessageChannel($outputChannelName)
                        ->build(
                            InMemoryChannelResolver::createFromAssociativeArray([
                                $inputChannelName => DirectChannel::create(),
                                $outputChannelName => $outputChannel
                            ]),
                            InMemoryReferenceSearchService::createWith([
                                $objectToInvokeReference => ServiceExpectingOneArgument::create()
                            ])
                        );

        $transformer->handle(MessageBuilder::withPayload($payload)->build());

        $this->assertMessages(
            MessageBuilder::withPayload($payload)
                ->build(),
            $outputChannel->receive()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_transforming_with_custom_method_arguments_converters()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $outputChannel = QueueChannel::create();
        $outputChannelName = 'output';
        $inputChannelName = 'input';
        $objectToInvokeReference = "service-a";
        $transformerBuilder = TransformerBuilder::create($inputChannelName, $objectToInvokeReference, 'withReturnValue')
                                ->withOutputMessageChannel($outputChannelName);
        $transformerBuilder->withMethodParameterConverters([
            MessageToPayloadParameterConverterBuilder::create('name'),
            MessageToHeaderParameterConverterBuilder::create('surname', 'token')
        ]);
        $transformer = $transformerBuilder
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => DirectChannel::create(),
                    $outputChannelName => $outputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    $objectToInvokeReference => ServiceExpectingTwoArguments::create()
                ])
            );

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

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_transforming_with_header_enricher()
    {
        $payload = 'someBigPayload';
        $headerValue = 'abc';
        $outputChannel = QueueChannel::create();
        $inputChannelName = "input";
        $outputChannelName = "output";
        $transformer = TransformerBuilder::createHeaderEnricher($inputChannelName, [
                "token" => $headerValue,
                "correlation-id" => 1
            ])
            ->withOutputMessageChannel($outputChannelName)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => DirectChannel::create(),
                    $outputChannelName => $outputChannel
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $transformer->handle(
            MessageBuilder::withPayload($payload)
                ->build()
        );

        $this->assertMessages(
            MessageBuilder::withPayload($payload)
                ->setHeader('token', $headerValue)
                ->setHeader('correlation-id', 1)
                ->build(),
            $outputChannel->receive()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_transforming_with_transformer_instance_of_object()
    {
        $referenceObject = ServiceWithReturnValue::create();

        $transformer = TransformerBuilder::createWithReferenceObject("inputChannel",  $referenceObject, "getName")
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createEmpty()
            );

        $replyChannel = QueueChannel::create();
        $transformer->handle(MessageBuilder::withPayload("some")->setReplyChannel($replyChannel)->build());

        $this->assertEquals(
            "johny",
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_transforming_payload_using_expression()
    {
        $payload = 13;
        $outputChannel = QueueChannel::create();

        $transformer = TransformerBuilder::createWithExpression("someChannel", "payload + 3")
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createWith([
                    ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
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
}