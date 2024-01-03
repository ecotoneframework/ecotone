<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\JsonToArray\JsonToArrayConverter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\SerializedToObject\DeserializingConverter;
use Ecotone\Messaging\Conversion\StringToUuid\StringToUuidConverter;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\Processor\WrapWithMessageBuildProcessor;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\Order;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderConfirmation;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderProcessor;
use Test\Ecotone\Messaging\Fixture\Converter\StringToUuidClassConverter;
use Test\Ecotone\Messaging\Fixture\Handler\ExampleService;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallMultipleUnorderedArgumentsInvocationInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithPassThroughInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\StubCallSavingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingMessageAndReturningMessage;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingThreeArguments;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingTwoArguments;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class MethodInvocationTest
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class MethodInvokerTest extends MessagingTest
{
    public function test_throwing_exception_if_not_enough_arguments_provided()
    {
        $this->expectException(InvalidArgumentException::class);

        $service = ServiceExpectingTwoArguments::create();
        $interfaceToCall = InterfaceToCall::create($service, 'withoutReturnValue');

        ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($service, InterfaceToCallReference::fromInstance($interfaceToCall)));
    }

    public function test_invoking_service()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();
        $interfaceToCall = InterfaceToCall::create($serviceExpectingOneArgument, 'withoutReturnValue');

        $methodInvocation = ComponentTestBuilder::create()
            ->build(
                MethodInvokerBuilder::create($serviceExpectingOneArgument, InterfaceToCallReference::fromInstance($interfaceToCall), [
                    PayloadBuilder::create('name'),
                ])
            );

        $methodInvocation->executeEndpoint(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($serviceExpectingOneArgument->wasCalled(), 'Method was not called');
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_not_changing_content_type_of_message_if_message_is_return_type()
    {
        $serviceExpectingOneArgument = ServiceExpectingMessageAndReturningMessage::create('test');
        $interfaceToCall = InterfaceToCall::create($serviceExpectingOneArgument, 'send');

        $methodInvocation = ComponentTestBuilder::create()
            ->build(
                MethodInvokerBuilder::create($serviceExpectingOneArgument, InterfaceToCallReference::fromInstance($interfaceToCall), [
                    MessageConverterBuilder::create('message'),
                ])
            );

        $message = MessageBuilder::withPayload('some')->build();
        $this->assertEquals(
            MessageBuilder::fromMessage($message)
                ->setPayload('test')
                ->build(),
            $methodInvocation->executeEndpoint($message)
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
        $interfaceToCall = InterfaceToCall::create($serviceExpectingOneArgument, 'withReturnValue');
        $headerName = 'token';
        $headerValue = '123X';

        $methodInvocation = ComponentTestBuilder::create()
            ->build(
                MethodInvokerBuilder::create($serviceExpectingOneArgument, InterfaceToCallReference::fromInstance($interfaceToCall), [
                    HeaderBuilder::create('name', $headerName),
                ])
            );

        $this->assertEquals(
            $headerValue,
            $methodInvocation->executeEndpoint(
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
        $interfaceToCall = InterfaceToCall::create($serviceExpectingOneArgument, 'withReturnValue');

        $methodInvocation = ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($serviceExpectingOneArgument, InterfaceToCallReference::fromInstance($interfaceToCall)));

        $payload = 'some';

        $this->assertEquals(
            $payload,
            $methodInvocation->executeEndpoint(
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
        $interfaceToCall = InterfaceToCall::create($serviceExpectingOneArgument, 'payloadAndHeaders');

        $methodInvocation = ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($serviceExpectingOneArgument, InterfaceToCallReference::fromInstance($interfaceToCall)));

        $payload = 'some';

        $this->assertEquals(
            $payload,
            $methodInvocation->executeEndpoint(
                MessageBuilder::withPayload($payload)
                    ->build()
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_passed_wrong_argument_names()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();
        $interfaceToCall = InterfaceToCall::create($serviceExpectingOneArgument, 'withoutReturnValue');

        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($serviceExpectingOneArgument, InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('wrongName'),
            ]));
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_invoking_service_with_multiple_not_ordered_arguments()
    {
        $serviceExpectingThreeArgument = ServiceExpectingThreeArguments::create();
        $interfaceToCall = InterfaceToCall::create($serviceExpectingThreeArgument, 'withReturnValue');

        $methodInvocation = ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($serviceExpectingThreeArgument, InterfaceToCallReference::fromInstance($interfaceToCall), [
                HeaderBuilder::create('surname', 'personSurname'),
                HeaderBuilder::create('age', 'personAge'),
                PayloadBuilder::create('name'),
            ]));

        $this->assertEquals(
            'johnybilbo13',
            $methodInvocation->executeEndpoint(
                MessageBuilder::withPayload('johny')
                    ->setHeader('personSurname', 'bilbo')
                    ->setHeader('personAge', 13)
                    ->build()
            )
        );
    }

    public function test_invoking_with_payload_conversion()
    {
        $interfaceToCall = InterfaceToCall::create(new OrderProcessor(), 'processOrder');

        $methodInvocation = ComponentTestBuilder::create()
            ->withReference(AutoCollectionConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([
                new DeserializingConverter(),
            ]))
            ->build(MethodInvokerBuilder::create(new OrderProcessor(), InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('order'),
            ]));
        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $message =
            MessageBuilder::withPayload(addslashes(serialize(Order::create('1', 'correct'))))
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build();

        $this->assertEquals(
            MessageBuilder::fromMessage($message)
                ->setPayload(OrderConfirmation::fromOrder(Order::create('1', 'correct')))
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(OrderConfirmation::class))
                ->build(),
            $methodInvocation->executeEndpoint($message)
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
            AutoCollectionConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([]),
        ]);
        $service   = new OrderProcessor();
        $interfaceToCall = InterfaceToCall::create($service, 'processOrder');

        $methodInvocation = ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($service, InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('order'),
            ]));

        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $this->expectException(InvalidArgumentException::class);

        $methodInvocation->executeEndpoint(
            MessageBuilder::withPayload(serialize(Order::create('1', 'correct')))
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build()
        );
    }

    public function test_calling_if_media_type_is_incompatible_but_types_are_fine()
    {
        $objectToInvoke         = new ExampleService();
        $interfaceToCall        = InterfaceToCall::create($objectToInvoke, 'receiveString');
        $methodInvocation = ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($objectToInvoke, InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('id'),
            ]));
        $methodInvocation       =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $result = $methodInvocation->executeEndpoint(
            MessageBuilder::withPayload('some')
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build()
        );

        $this->assertEquals('some', $result->getPayload());
    }

    public function test_calling_if_when_parameter_is_union_type_and_argument_compatible_with_second()
    {
        $service                = new ServiceExpectingOneArgument();
        $interfaceToCall        = InterfaceToCall::create($service, 'withUnionParameter');
        $methodInvocation = ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($service, InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('value'),
            ]));
        $methodInvocation       =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $result = $methodInvocation->executeEndpoint(
            MessageBuilder::withPayload('some')
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build()
        );

        $this->assertEquals('some', $result->getPayload());
    }

    public function test_invoking_with_conversion_based_on_type_id_when_declaration_is_interface()
    {
        $interfaceToCall = InterfaceToCall::create(new ServiceExpectingOneArgument(), 'withInterface');
        $methodInvocation = ComponentTestBuilder::create()
            ->withReference(AutoCollectionConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([
                new StringToUuidClassConverter(),
            ]))
            ->build(MethodInvokerBuilder::create(new ServiceExpectingOneArgument(), InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('value'),
            ]));
        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $data      = '893a660c-0208-4140-8be6-95fb2dcd2fdd';
        $replyMessage = $methodInvocation->executeEndpoint(
            MessageBuilder::withPayload($data)
                ->setHeader(MessageHeaders::TYPE_ID, Uuid::class)
                ->setContentType(MediaType::createApplicationXPHP())
                ->build()
        );

        $this->assertEquals(
            Uuid::fromString('893a660c-0208-4140-8be6-95fb2dcd2fdd'),
            $replyMessage->getPayload()
        );
    }

    public function test_invoking_with_conversion_and_union_type_resolving_type_from_type_header_with_different_media_type()
    {
        $interfaceToCall = InterfaceToCall::create(new ServiceExpectingOneArgument(), 'withUnionParameterWithArray');

        $methodInvocation = ComponentTestBuilder::create()
            ->withReference(AutoCollectionConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([
                new JsonToArrayConverter(),
            ]))
            ->build(MethodInvokerBuilder::create(new ServiceExpectingOneArgument(), InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('value'),
            ]));
        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $data      = '["893a660c-0208-4140-8be6-95fb2dcd2fdd"]';
        $replyMessage = $methodInvocation->executeEndpoint(
            MessageBuilder::withPayload($data)
                ->setHeader(MessageHeaders::TYPE_ID, TypeDescriptor::ARRAY)
                ->setContentType(MediaType::createApplicationJson())
                ->build()
        );

        $this->assertEquals(
            ['893a660c-0208-4140-8be6-95fb2dcd2fdd'],
            $replyMessage->getPayload()
        );
    }

    public function test_throwing_exception_if_deserializing_to_union_without_type_header()
    {
        $interfaceToCall = InterfaceToCall::create(new ServiceExpectingOneArgument(), 'withUnionParameterWithArray');

        $this->expectException(InvalidArgumentException::class);

        $methodInvocation = ComponentTestBuilder::create()
            ->withReference(AutoCollectionConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([
                new JsonToArrayConverter(),
            ]))
            ->build(MethodInvokerBuilder::create(new ServiceExpectingOneArgument(), InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('value'),
            ]));

        $methodInvocation =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $methodInvocation->executeEndpoint(
            MessageBuilder::withPayload('["893a660c-0208-4140-8be6-95fb2dcd2fdd"]')
                ->setContentType(MediaType::createApplicationJson())
                ->build()
        );
    }

    public function test_invoking_with_header_conversion_for_union_type_parameter()
    {
        $service = new ServiceExpectingOneArgument();
        $methodInvocation = ComponentTestBuilder::create()
            ->withReference(AutoCollectionConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([
                new StringToUuidConverter(),
            ]))
            ->build(MethodInvokerBuilder::create($service, new InterfaceToCallReference(ServiceExpectingOneArgument::class, 'withUnionParameterWithUuid'), [
                HeaderBuilder::create('value', 'uuid'),
            ]));



        $uuid = 'fd825894-907c-4c6c-88a9-ae1ecdf3d307';
        $replyMessage = $methodInvocation->executeEndpoint(
            MessageBuilder::withPayload('some')
                ->setHeader('uuid', $uuid)
                ->setContentType(MediaType::createTextPlain())
                ->build()
        );

        $this->assertEquals(
            Uuid::fromString($uuid),
            $replyMessage
        );
    }

    public function test_if_can_not_decide_return_type_make_use_resolved_from_return_value_for_array()
    {
        $service                = new ServiceExpectingOneArgument();
        $interfaceToCall        = InterfaceToCall::create($service, 'withCollectionAndArrayReturnType');
        $methodInvocation = ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($service, InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('value'),
            ]));
        $methodInvocation       =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $replyMessage = $methodInvocation->executeEndpoint(MessageBuilder::withPayload(['test'])->build());

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter('array<string>')->toString(),
            $replyMessage->getHeaders()->getContentType()->toString()
        );
    }

    public function test_if_can_decide_based_on_return_type_then_should_be_used_for_array()
    {
        $service                = new ServiceExpectingOneArgument();
        $interfaceToCall        = InterfaceToCall::create($service, 'withCollectionAndArrayReturnType');
        $methodInvocation = ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($service, InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('value'),
            ]));
        $methodInvocation       =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $replyMessage = $methodInvocation->executeEndpoint(MessageBuilder::withPayload([new stdClass()])->build());

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter('array<stdClass>')->toString(),
            $replyMessage->getHeaders()->getContentType()->toString()
        );
    }

    public function test_given_return_type_is_union_then_should_decide_on_return_type_based_on_return_variable()
    {
        $service                = new ServiceExpectingOneArgument();
        $interfaceToCall        = InterfaceToCall::create($service, 'withUnionReturnType');
        $methodInvocation = ComponentTestBuilder::create()
            ->build(MethodInvokerBuilder::create($service, InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('value'),
            ]));
        $methodInvocation       =
            WrapWithMessageBuildProcessor::createWith(
                $interfaceToCall,
                $methodInvocation,
            );

        $replyMessage = $methodInvocation->executeEndpoint(MessageBuilder::withPayload(new stdClass())->build());

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(stdClass::class)->toString(),
            $replyMessage->getHeaders()->getContentType()->toString()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_invoking_with_header_conversion()
    {
        $orderProcessor   = new OrderProcessor();
        $interfaceToCall = InterfaceToCall::create($orderProcessor, 'buyByName');

        $methodInvocation = ComponentTestBuilder::create()
            ->withReference(AutoCollectionConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([
                new StringToUuidConverter(),
            ]))
            ->build(MethodInvokerBuilder::create($orderProcessor, InterfaceToCallReference::fromInstance($interfaceToCall), [
                HeaderBuilder::create('id', 'uuid'),
            ]));

        $uuid = 'fd825894-907c-4c6c-88a9-ae1ecdf3d307';
        $replyMessage = $methodInvocation->executeEndpoint(
            MessageBuilder::withPayload('some')
                ->setHeader('uuid', $uuid)
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
        $service   = new OrderProcessor();
        $interfaceToCall = InterfaceToCall::create($service, 'buyMultiple');

        $methodInvocation = ComponentTestBuilder::create()
            ->withReference(AutoCollectionConversionService::REFERENCE_NAME, AutoCollectionConversionService::createWith([
                new StringToUuidConverter(),
            ]))
            ->build(MethodInvokerBuilder::create($service, InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('ids'),
            ]));

        $replyMessage = $methodInvocation->executeEndpoint(
            MessageBuilder::withPayload(['fd825894-907c-4c6c-88a9-ae1ecdf3d307', 'fd825894-907c-4c6c-88a9-ae1ecdf3d308'])
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter('array<string>'))
                ->build()
        );

        $this->assertEquals(
            [OrderConfirmation::createFromUuid(Uuid::fromString('fd825894-907c-4c6c-88a9-ae1ecdf3d307')), OrderConfirmation::createFromUuid(Uuid::fromString('fd825894-907c-4c6c-88a9-ae1ecdf3d308'))],
            $replyMessage
        );
    }

    public function test_calling_interceptor_with_multiple_unordered_arguments()
    {
        $interceptingService1 = CallMultipleUnorderedArgumentsInvocationInterceptorExample::create();
        $interceptedService = StubCallSavingService::create();
        $interfaceToCall = InterfaceToCall::create($interceptedService, 'callWithMultipleArguments');
        $methodInvocation = ComponentTestBuilder::create()
            ->withReference(CallMultipleUnorderedArgumentsInvocationInterceptorExample::class, $interceptingService1)
            ->build(MethodInvokerBuilder::create($interceptedService, InterfaceToCallReference::fromInstance($interfaceToCall), [
                PayloadBuilder::create('some'),
                HeaderBuilder::create('numbers', 'numbers'),
                HeaderBuilder::create('strings', 'strings'),
            ]));

        $message = MessageBuilder::withPayload(new stdClass())
            ->setHeader('numbers', [5, 1])
            ->setHeader('strings', ['string1', 'string2'])
            ->build();
        $methodInvocation->executeEndpoint($message);

        $this->assertTrue($interceptedService->wasCalled(), 'Intercepted Service was not called');
    }

    public function test_passing_through_message_when_calling_interceptor_without_method_invocation()
    {
        $interceptingService1 = CallWithPassThroughInterceptorExample::create();
        $interceptedService = StubCallSavingService::createWithReturnType('some');
        $interfaceToCall = InterfaceToCall::create($interceptedService, 'callWithReturn');

        $methodInvocation = ComponentTestBuilder::create()
            ->withReference(CallWithPassThroughInterceptorExample::class, $interceptingService1)
            ->build(MethodInvokerBuilder::create($interceptedService, InterfaceToCallReference::fromInstance($interfaceToCall)));

        $this->assertEquals(
            'some',
            $methodInvocation->executeEndpoint(MessageBuilder::withPayload(new stdClass())->build())
        );
    }
}
