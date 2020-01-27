<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\SerializedToObject\DeserializingConverter;
use Ecotone\Messaging\Conversion\StringToUuid\StringToUuidConverter;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocationException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\Processor\WrapWithMessageBuildProcessor;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\Order;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderConfirmation;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderProcessor;
use Test\Ecotone\Messaging\Fixture\Handler\ExampleService;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\AroundInterceptorObjectBuilderExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallMultipleUnorderedArgumentsInvocationInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithAnnotationFromClassInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithAnnotationFromMethodInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithEndingChainAndReturningInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithEndingChainNoReturningInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithInterceptedObjectInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithNullableStdClassInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithPassThroughInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithProceedingAndReturningInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithProceedingInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithReferenceSearchServiceExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithReplacingArgumentsInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithRequestMessageInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithStdClassInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithUnorderedClassInvocationInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\StubCallSavingService;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingMessageAndReturningMessage;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingThreeArguments;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingTwoArguments;
use Test\Ecotone\Messaging\Fixture\Service\ServiceWithoutAnyMethods;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class MethodInvocationTest
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInvokerTest extends MessagingTest
{
    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_class_has_no_defined_method()
    {
        $this->expectException(InvalidArgumentException::class);

        MethodInvoker::createWith(ServiceWithoutAnyMethods::create(), 'getName', [], InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_not_enough_arguments_provided()
    {
        $this->expectException(InvalidArgumentException::class);

        MethodInvoker::createWith(ServiceExpectingTwoArguments::create(), 'withoutReturnValue', [], InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_invoking_service()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();

        $methodInvocation = MethodInvoker::createWith($serviceExpectingOneArgument, 'withoutReturnValue', [
            PayloadBuilder::create('name')
        ], InMemoryReferenceSearchService::createEmpty());

        $methodInvocation->processMessage(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($serviceExpectingOneArgument->wasCalled(), "Method was not called");
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_not_changing_content_type_of_message_if_message_is_return_type()
    {
        $serviceExpectingOneArgument = ServiceExpectingMessageAndReturningMessage::create("test");

        $methodInvocation = MethodInvoker::createWith($serviceExpectingOneArgument, 'send', [
            MessageConverterBuilder::create("message")
        ], InMemoryReferenceSearchService::createEmpty());

        $this->assertMessages(
            MessageBuilder::withPayload("test")
                ->build(),
            $methodInvocation->processMessage(MessageBuilder::withPayload('some')->build())
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_invoking_service_with_return_value_from_header()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();
        $headerName = 'token';
        $headerValue = '123X';

        $methodInvocation = MethodInvoker::createWith($serviceExpectingOneArgument, 'withReturnValue', [
            HeaderBuilder::create('name', $headerName)
        ], InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals($headerValue,
            $methodInvocation->processMessage(
                MessageBuilder::withPayload('some')
                    ->setHeader($headerName, $headerValue)
                    ->build()
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_if_method_requires_one_argument_and_there_was_not_passed_any_then_use_payload_one_as_default()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();

        $methodInvocation = MethodInvoker::createWith($serviceExpectingOneArgument, 'withReturnValue', [], InMemoryReferenceSearchService::createEmpty());

        $payload = 'some';

        $this->assertEquals($payload,
            $methodInvocation->processMessage(
                MessageBuilder::withPayload($payload)
                    ->build()
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_if_method_requires_two_argument_and_there_was_not_passed_any_then_use_payload_and_headers_if_possible_as_default()
    {
        $serviceExpectingOneArgument = ServiceExpectingTwoArguments::create();

        $methodInvocation = MethodInvoker::createWith($serviceExpectingOneArgument, 'payloadAndHeaders', [], InMemoryReferenceSearchService::createEmpty());

        $payload = 'some';

        $this->assertEquals(
            $payload,
            $methodInvocation->processMessage(
                MessageBuilder::withPayload($payload)
                    ->build()
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_passed_wrong_argument_names()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();

        $this->expectException(InvalidArgumentException::class);

        MethodInvoker::createWith($serviceExpectingOneArgument, 'withoutReturnValue', [
            PayloadBuilder::create('wrongName')
        ], InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_invoking_service_with_multiple_not_ordered_arguments()
    {
        $serviceExpectingThreeArgument = ServiceExpectingThreeArguments::create();

        $methodInvocation = MethodInvoker::createWith($serviceExpectingThreeArgument, 'withReturnValue', [
            HeaderBuilder::create('surname', 'personSurname'),
            HeaderBuilder::create('age', 'personAge'),
            PayloadBuilder::create('name'),
        ], InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals("johnybilbo13",
            $methodInvocation->processMessage(
                MessageBuilder::withPayload('johny')
                    ->setHeader('personSurname', 'bilbo')
                    ->setHeader('personAge', 13)
                    ->build()
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_invoking_with_payload_conversion()
    {
        $referenceSearchService = InMemoryReferenceSearchService::createWith([
            AutoCollectionConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([
                new DeserializingConverter()
            ])
        ]);
        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                new OrderProcessor(), 'processOrder',
                MethodInvoker::createWith(new OrderProcessor(), 'processOrder', [
                    PayloadBuilder::create('order')
                ], $referenceSearchService),
                $referenceSearchService
            );

        $replyMessage = $methodInvocation->processMessage(
            MessageBuilder::withPayload(serialize(Order::create('1', "correct")))
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build()
        );

        $this->assertMessages(
            MessageBuilder::withPayload(OrderConfirmation::fromOrder(Order::create('1', "correct")))
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(OrderConfirmation::class))
                ->build(),
            $replyMessage
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_cannot_convert_to_php_media_type()
    {
        $referenceSearchService = InMemoryReferenceSearchService::createWith([
            AutoCollectionConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([])
        ]);
        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                new OrderProcessor(), 'processOrder',
                MethodInvoker::createWith(new OrderProcessor(), 'processOrder', [
                    PayloadBuilder::create('order')
                ], $referenceSearchService),
                $referenceSearchService
            );

        $this->expectException(InvalidArgumentException::class);

        $methodInvocation->processMessage(
            MessageBuilder::withPayload(serialize(Order::create('1', "correct")))
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build()
        );
    }

    public function test_calling_if_media_type_is_incompatible_but_types_are()
    {
        $referenceSearchService = InMemoryReferenceSearchService::createWith([
            AutoCollectionConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([])
        ]);
        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                new OrderProcessor(), 'processOrder',
                MethodInvoker::createWith(new ExampleService(), 'receiveString', [
                    PayloadBuilder::create('id')
                ], $referenceSearchService),
                $referenceSearchService
            );

        $result = $methodInvocation->processMessage(
            MessageBuilder::withPayload("some")
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build()
        );

        $this->assertEquals("some", $result->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_choosing_one_of_compatible_return_union_type_when_first_is_correct()
    {
        $referenceSearchService = InMemoryReferenceSearchService::createEmpty();
        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                new ServiceExpectingOneArgument(), 'withDifferentScalarOrObjectReturnType',
                MethodInvoker::createWith(new ServiceExpectingOneArgument(), 'withDifferentScalarOrObjectReturnType', [
                    PayloadBuilder::create('value')
                ], $referenceSearchService),
                $referenceSearchService
            );

        $replyMessage = $methodInvocation->processMessage(MessageBuilder::withPayload(new stdClass())->build());

        $this->assertMessages(
            MessageBuilder::withPayload(new stdClass())
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(stdClass::class))
                ->build(),
            $replyMessage
        );
    }

    public function test_choosing_one_of_compatible_return_union_type_when_first_is_incorrect()
    {
        $referenceSearchService = InMemoryReferenceSearchService::createEmpty();
        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                new ServiceExpectingOneArgument(), 'withDifferentScalarOrObjectReturnType',
                MethodInvoker::createWith(new ServiceExpectingOneArgument(), 'withDifferentScalarOrObjectReturnType', [
                    PayloadBuilder::create('value')
                ], $referenceSearchService),
                $referenceSearchService
            );

        $replyMessage = $methodInvocation->processMessage(MessageBuilder::withPayload("test")->build());

        $this->assertMessages(
            MessageBuilder::withPayload("test")
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter("string"))
                ->build(),
            $replyMessage
        );
    }

    public function test_if_can_not_decide_return_type_make_use_resolved_from_return_value()
    {
        $referenceSearchService = InMemoryReferenceSearchService::createEmpty();
        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                new ServiceExpectingOneArgument(), 'withCollectionAndArrayReturnType',
                MethodInvoker::createWith(new ServiceExpectingOneArgument(), 'withCollectionAndArrayReturnType', [
                    PayloadBuilder::create('value')
                ], $referenceSearchService),
                $referenceSearchService
            );

        $replyMessage = $methodInvocation->processMessage(MessageBuilder::withPayload("test")->build());

        $this->assertMessages(
            MessageBuilder::withPayload("test")
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter("string"))
                ->build(),
            $replyMessage
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_invoking_with_header_conversion()
    {
        $methodInvocation = MethodInvoker::createWith(new OrderProcessor(), 'buyByName', [
            HeaderBuilder::create("id", "uuid")
        ], InMemoryReferenceSearchService::createWith([
            AutoCollectionConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([
                new StringToUuidConverter()
            ])
        ]));

        $uuid = "fd825894-907c-4c6c-88a9-ae1ecdf3d307";
        $replyMessage = $methodInvocation->processMessage(
            MessageBuilder::withPayload("some")
                ->setHeader("uuid", $uuid)
                ->setContentType(MediaType::createTextPlain())
                ->build()
        );

        $this->assertEquals(
            OrderConfirmation::createFromUuid(Uuid::fromString($uuid)),
            $replyMessage
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_invoking_with_converter_for_collection_if_types_are_compatible()
    {
        $methodInvocation = MethodInvoker::createWith(new OrderProcessor(), 'buyMultiple', [
            PayloadBuilder::create("ids")
        ], InMemoryReferenceSearchService::createWith([
            AutoCollectionConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([
                new StringToUuidConverter()
            ])
        ]));

        $replyMessage = $methodInvocation->processMessage(
            MessageBuilder::withPayload(["fd825894-907c-4c6c-88a9-ae1ecdf3d307", "fd825894-907c-4c6c-88a9-ae1ecdf3d308"])
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter("array<string>"))
                ->build()
        );

        $this->assertEquals(
            [OrderConfirmation::createFromUuid(Uuid::fromString("fd825894-907c-4c6c-88a9-ae1ecdf3d307")), OrderConfirmation::createFromUuid(Uuid::fromString("fd825894-907c-4c6c-88a9-ae1ecdf3d308"))],
            $replyMessage
        );
    }

    public function test_calling_with_single_around_advice_proceeding_method_invocation()
    {
        $interceptingService1 = CallWithProceedingInterceptorExample::create();
        $interceptedService = StubCallSavingService::create();
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callNoArgumentsAndReturnType', [],
            InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithProceedingInterceptorExample::class => $interceptingService1
        ]),
            [
                AroundInterceptorReference::createWithNoPointcut("someId", CallWithProceedingInterceptorExample::class, "callWithProceeding")
            ]
        );

        $methodInvocation->processMessage(MessageBuilder::withPayload("some")->build());
        $this->assertTrue($interceptedService->wasCalled());
        $this->assertTrue($interceptingService1->wasCalled());
    }

    public function test_calling_with_around_interceptor_from_object_builder()
    {
        $interceptingService1 = StubCallSavingService::create();
        $interceptedService = StubCallSavingService::create();
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callNoArgumentsAndReturnType', [],
            InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty(),
            [
                AroundInterceptorReference::createWithObjectBuilder("someId", AroundInterceptorObjectBuilderExample::create($interceptingService1), "callWithProceed", 0, "")
            ]
        );

        $methodInvocation->processMessage(MessageBuilder::withPayload("some")->build());
        $this->assertTrue($interceptedService->wasCalled());
        $this->assertTrue($interceptingService1->wasCalled());
    }

    public function test_calling_with_multiple_around_advice_proceeding_method_invocation()
    {
        $interceptingService1 = CallWithProceedingInterceptorExample::create();
        $interceptingService2 = CallWithProceedingInterceptorExample::create();
        $interceptingService3 = CallWithProceedingInterceptorExample::create();
        $interceptedService = StubCallSavingService::create();
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callNoArgumentsAndReturnType', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            "interceptor1" => $interceptingService1,
            "interceptor2" => $interceptingService2,
            "interceptor3" => $interceptingService3
        ]),
            [
                AroundInterceptorReference::createWithNoPointcut("someId", "interceptor1", "callWithProceeding"),
                AroundInterceptorReference::createWithNoPointcut("someId", "interceptor2", "callWithProceeding"),
                AroundInterceptorReference::createWithNoPointcut("someId", "interceptor3", "callWithProceeding")
            ]
        );

        $methodInvocation->processMessage(MessageBuilder::withPayload("some")->build());
        $this->assertTrue($interceptedService->wasCalled());
        $this->assertTrue($interceptingService1->wasCalled());
        $this->assertTrue($interceptingService2->wasCalled());
        $this->assertTrue($interceptingService3->wasCalled());
    }

    public function test_calling_with_around_method_interceptor_changing_return_value()
    {
        $interceptingService1 = CallWithEndingChainAndReturningInterceptorExample::createWithReturnType("changed");
        $interceptedService = StubCallSavingService::createWithReturnType("original");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callNoArgumentsAndReturnType', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithEndingChainAndReturningInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithEndingChainAndReturningInterceptorExample::class, "callWithEndingChainAndReturning")]
        );

        $this->assertFalse($interceptedService->wasCalled());
        $this->assertEquals(
            "changed",
            $methodInvocation->processMessage(MessageBuilder::withPayload("some")->build())
        );
    }

    public function test_throwing_exception_when_replacing_arguments_when_blocked()
    {
        $methodInvocation = MethodInvoker::createWithInterceptorsNotChangingCallArgumentsWithChannel(
            CalculatingService::create(3), 'result', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty(),
            [AroundInterceptorReference::createWithDirectObject("",
                CalculatingServiceInterceptorExample::create(1), "sum",
                0,
                ""
            )],
            []
        );

        $this->expectException(InvalidArgumentException::class);

        $methodInvocation->processMessage(MessageBuilder::withPayload(5)->build());
    }

    public function test_calling_with_method_interceptor_changing_return_value_at_second_call()
    {
        $interceptingService1 = CallWithProceedingAndReturningInterceptorExample::create();
        $interceptingService2 = CallWithEndingChainAndReturningInterceptorExample::createWithReturnType("changed");
        $interceptedService = StubCallSavingService::createWithReturnType("original");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithReturn', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithProceedingAndReturningInterceptorExample::class => $interceptingService1,
            CallWithEndingChainAndReturningInterceptorExample::class => $interceptingService2
        ]),
            [
                AroundInterceptorReference::createWithNoPointcut("someId", CallWithProceedingAndReturningInterceptorExample::class, "callWithProceedingAndReturning"),
                AroundInterceptorReference::createWithNoPointcut("someId", CallWithEndingChainAndReturningInterceptorExample::class, "callWithEndingChainAndReturning")
            ]
        );

        $this->assertEquals(
            "changed",
            $methodInvocation->processMessage(MessageBuilder::withPayload("some")->build())
        );
    }

    public function test_calling_with_interceptor_ending_call_and_return_nothing()
    {
        $interceptingService1 = CallWithEndingChainNoReturningInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("original");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithReturn', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithEndingChainNoReturningInterceptorExample::class => $interceptingService1
        ]),
            [
                AroundInterceptorReference::createWithNoPointcut("someId", CallWithEndingChainNoReturningInterceptorExample::class, "callWithEndingChainNoReturning")
            ]
        );

        $this->assertNull($methodInvocation->processMessage(MessageBuilder::withPayload("some")->build()));
    }

    public function test_changing_calling_arguments_from_interceptor()
    {
        $interceptingService1 = CallWithReplacingArgumentsInterceptorExample::createWithArgumentsToReplace(["stdClass" => new stdClass()]);
        $interceptedService = StubCallSavingService::create();
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithStdClassArgument', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithReplacingArgumentsInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithReplacingArgumentsInterceptorExample::class, "callWithReplacingArguments")]
        );

        $this->assertNull($methodInvocation->processMessage(MessageBuilder::withPayload("some")->build()));
        $this->assertTrue($interceptedService->wasCalled());
    }

    public function test_calling_interceptor_with_unordered_arguments_from_intercepted_method()
    {
        $interceptingService1 = CallWithUnorderedClassInvocationInterceptorExample::create();
        $interceptedService = StubCallSavingService::create();
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithStdClassAndIntArgument', [
            PayloadBuilder::create("some"),
            HeaderBuilder::create("number", "number")
        ], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithUnorderedClassInvocationInterceptorExample::class => $interceptingService1
        ]),
            [
                AroundInterceptorReference::createWithNoPointcut("someId", CallWithUnorderedClassInvocationInterceptorExample::class, "callWithUnorderedClassInvocation")
            ]
        );

        $message = MessageBuilder::withPayload(new stdClass())
            ->setHeader("number", 5)
            ->build();
        $methodInvocation->processMessage($message);

        $this->assertTrue($interceptedService->wasCalled(), "Intercepted Service was not called");
    }

    public function test_calling_interceptor_with_multiple_unordered_arguments()
    {
        $interceptingService1 = CallMultipleUnorderedArgumentsInvocationInterceptorExample::create();
        $interceptedService = StubCallSavingService::create();
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithMultipleArguments', [
            PayloadBuilder::create("some"),
            HeaderBuilder::create("numbers", "numbers"),
            HeaderBuilder::create("strings", "strings")
        ], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallMultipleUnorderedArgumentsInvocationInterceptorExample::class => $interceptingService1
        ]),
            [
                AroundInterceptorReference::createWithNoPointcut("someId", CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, "callMultipleUnorderedArgumentsInvocation")
            ]
        );

        $message = MessageBuilder::withPayload(new stdClass())
            ->setHeader("numbers", [5, 1])
            ->setHeader("strings", ["string1", "string2"])
            ->build();
        $methodInvocation->processMessage($message);

        $this->assertTrue($interceptedService->wasCalled(), "Intercepted Service was not called");
    }

    public function test_passing_through_message_when_calling_interceptor_without_method_invocation()
    {
        $interceptingService1 = CallWithPassThroughInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithReturn', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithPassThroughInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithPassThroughInterceptorExample::class, "callWithPassThrough")]
        );

        $this->assertEquals(
            "some",
            $methodInvocation->processMessage(MessageBuilder::withPayload(new stdClass())->build())
        );
    }

    public function test_calling_interceptor_with_intercepted_object_instance()
    {
        $interceptingService1 = CallWithInterceptedObjectInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithReturn', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithInterceptedObjectInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithInterceptedObjectInterceptorExample::class, "callWithInterceptedObject")]
        );

        $this->assertEquals(
            "some",
            $methodInvocation->processMessage(MessageBuilder::withPayload(new stdClass())->build())
        );
    }

    public function test_calling_interceptor_with_request_message()
    {
        $interceptingService1 = CallWithRequestMessageInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithReturn', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithRequestMessageInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithRequestMessageInterceptorExample::class, "callWithRequestMessage")]
        );

        $requestMessage = MessageBuilder::withPayload(new stdClass())->build();
        $this->assertEquals(
            $requestMessage,
            $methodInvocation->processMessage($requestMessage)
        );
    }

    public function test_not_throwing_exception_when_can_not_resolve_argument_when_parameter_is_nullable()
    {
        $interceptingService1 = CallWithNullableStdClassInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithReturn', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithNullableStdClassInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithNullableStdClassInterceptorExample::class, "callWithNullableStdClass")]
        );

        $requestMessage = MessageBuilder::withPayload("test")->build();
        $this->assertNull($methodInvocation->processMessage($requestMessage));
    }

    public function test_throwing_exception_if_cannot_resolve_arguments_for_interceptor()
    {
        $interceptingService1 = CallMultipleUnorderedArgumentsInvocationInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithReturn', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallMultipleUnorderedArgumentsInvocationInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, "callMultipleUnorderedArgumentsInvocation")]
        );

        $this->expectException(MethodInvocationException::class);

        $this->assertEquals(
            "some",
            $methodInvocation->processMessage(MessageBuilder::withPayload(new stdClass())->build())
        );
    }

    public function test_calling_interceptor_with_method_annotation()
    {
        $interceptingService1 = CallWithAnnotationFromMethodInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'methodWithAnnotation', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithAnnotationFromMethodInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithAnnotationFromMethodInterceptorExample::class, "callWithMethodAnnotation")]
        );

        $requestMessage = MessageBuilder::withPayload("test")->build();
        $this->assertNull($methodInvocation->processMessage($requestMessage));
    }

    public function test_calling_interceptor_with_class_annotation()
    {
        $interceptingService1 = CallWithAnnotationFromClassInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'methodWithAnnotation', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithAnnotationFromClassInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithAnnotationFromClassInterceptorExample::class, "callWithMethodAnnotation")]
        );

        $requestMessage = MessageBuilder::withPayload("test")->build();
        $this->assertNull($methodInvocation->processMessage($requestMessage));
    }

    public function test_calling_interceptor_with_endpoint_annotation()
    {
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'methodWithAnnotation', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty(),
            [AroundInterceptorReference::createWithDirectObject("someId", CallWithStdClassInterceptorExample::create(), "callWithStdClass", 0, "")],
            [
                new stdClass()
            ]
        );

        $requestMessage = MessageBuilder::withPayload("test")->build();
        $this->assertNull($methodInvocation->processMessage($requestMessage));
    }

    public function test_calling_interceptor_with_reference_search_service()
    {
        $interceptingService1 = CallWithReferenceSearchServiceExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'methodWithAnnotation', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithReferenceSearchServiceExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithReferenceSearchServiceExample::class, "call")]
        );

        $requestMessage = MessageBuilder::withPayload("test")->build();
        $this->assertNull($methodInvocation->processMessage($requestMessage));
    }

    public function test_throwing_exception_if_registering_around_method_interceptor_with_return_value_but_without_method_invocation()
    {
        $interceptingService1 = CalculatingService::create(0);
        $interceptedService = StubCallSavingService::createWithReturnType("some");

        $this->expectException(InvalidArgumentException::class);

        MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithReturn', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CalculatingService::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CalculatingService::class, "sum")]
        );
    }

    public function test_passing_endpoint_annotation()
    {
        $interceptingService1 = CallWithStdClassInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'methodWithAnnotation', [], InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithStdClassInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithStdClassInterceptorExample::class, "callWithStdClass")],
            [new stdClass()]
        );

        $requestMessage = MessageBuilder::withPayload("test")->build();
        $this->assertNull($methodInvocation->processMessage($requestMessage));
    }

    public function test_passing_payload_if_compatible()
    {
        $interceptingService1 = CallWithStdClassInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithMessage', [],
            InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithStdClassInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithStdClassInterceptorExample::class, "callWithStdClass")],
            []
        );

        $requestMessage = MessageBuilder::withPayload(new stdClass())
            ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(stdClass::class))
            ->build();
        $this->assertNull($methodInvocation->processMessage($requestMessage));
    }

    public function test_passing_headers_if_compatible()
    {
        $interceptingService1 = CallWithStdClassInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType("some");
        $methodInvocation = MethodInvoker::createWithInterceptorsWithChannel(
            $interceptedService, 'callWithMessage', [],
            InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            CallWithStdClassInterceptorExample::class => $interceptingService1
        ]),
            [AroundInterceptorReference::createWithNoPointcut("someId", CallWithStdClassInterceptorExample::class, "callWithStdClassAndHeaders")],
            []
        );

        $mediaType = MediaType::createApplicationXPHPWithTypeParameter(stdClass::class);
        $requestMessage = MessageBuilder::withPayload(new stdClass())
            ->setContentType($mediaType)
            ->setHeader("token", "123")
            ->build();

        $methodInvocation->processMessage($requestMessage);
        $headers = $interceptingService1->getCalledHeaders();
        unset($headers[MessageHeaders::MESSAGE_ID], $headers[MessageHeaders::TIMESTAMP]);

        $this->assertEquals(
            [
                "token" => "123",
                MessageHeaders::CONTENT_TYPE => $mediaType->toString()
            ],
            $headers
        );
    }
}