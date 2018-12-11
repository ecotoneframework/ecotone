<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Processor;

use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Ordering\Order;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Ordering\OrderConfirmation;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Ordering\OrderProcessor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingMessageAndReturningMessage;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingThreeArguments;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingTwoArguments;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceWithoutAnyMethods;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\DeserializingConverter;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Conversion\StringToUuidConverter;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\WrapWithMessageBuildProcessor;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class MethodInvocationTest
 * @package SimplyCodedSoftware\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInvokerTest extends MessagingTest
{
    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_class_has_no_defined_method()
    {
        $this->expectException(InvalidArgumentException::class);

        MethodInvoker::createWith(ServiceWithoutAnyMethods::create(), 'getName', [], InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_not_enough_arguments_provided()
    {
        $this->expectException(InvalidArgumentException::class);

        MethodInvoker::createWith(ServiceExpectingTwoArguments::create(), 'withoutReturnValue', [], InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_not_changing_content_type_of_message_if_message_is_return()
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
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
                ->setContentType(MediaType::APPLICATION_X_PHP_SERIALIZED_OBJECT)
                ->build()
        );

        $this->assertMessages(
              MessageBuilder::withPayload(OrderConfirmation::fromOrder(Order::create('1', "correct")))
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter("\\" . OrderConfirmation::class)->toString())
                ->build(),
              $replyMessage
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_invoking_with_header_conversion()
    {
        $methodInvocation = MethodInvoker::createWithMessageWrapper(new OrderProcessor(), 'buyByName', [
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
                ->setContentType(MediaType::createTextPlain()->toString())
                ->build()
        );

        $this->assertEquals(
            OrderConfirmation::createFromUuid(Uuid::fromString($uuid)),
            $replyMessage
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_invoking_with_converter_for_collection_if_types_are_compatible()
    {
        $methodInvocation = MethodInvoker::createWithMessageWrapper(new OrderProcessor(), 'buyMultiple', [
            PayloadBuilder::create("ids")
        ], InMemoryReferenceSearchService::createWith([
            AutoCollectionConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([
                new StringToUuidConverter()
            ])
        ]));

        $replyMessage = $methodInvocation->processMessage(
            MessageBuilder::withPayload(["fd825894-907c-4c6c-88a9-ae1ecdf3d307", "fd825894-907c-4c6c-88a9-ae1ecdf3d308"])
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter("array<string>")->toString())
                ->build()
        );

        $this->assertEquals(
            [OrderConfirmation::createFromUuid(Uuid::fromString("fd825894-907c-4c6c-88a9-ae1ecdf3d307")), OrderConfirmation::createFromUuid(Uuid::fromString("fd825894-907c-4c6c-88a9-ae1ecdf3d308"))],
            $replyMessage
        );
    }


}