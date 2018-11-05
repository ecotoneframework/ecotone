<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Processor;

use Fixture\Behat\Ordering\Order;
use Fixture\Behat\Ordering\OrderConfirmation;
use Fixture\Behat\Ordering\OrderProcessor;
use Fixture\Service\ServiceExpectingMessageAndReturningMessage;
use Fixture\Service\ServiceExpectingOneArgument;
use Fixture\Service\ServiceExpectingThreeArguments;
use Fixture\Service\ServiceExpectingTwoArguments;
use Fixture\Service\ServiceWithoutAnyMethods;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\DeserializingConverter;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\MediaType;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\StringToUuidConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\HeaderConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\PayloadConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class MethodInvocationTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInvokerTest extends MessagingTest
{
    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_class_has_no_defined_method()
    {
        $this->expectException(InvalidArgumentException::class);

        MethodInvoker::createWith(ServiceWithoutAnyMethods::create(), 'getName', [], true, InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_not_enough_arguments_provided()
    {
        $this->expectException(InvalidArgumentException::class);

        MethodInvoker::createWith(ServiceExpectingTwoArguments::create(), 'withoutReturnValue', [], true, InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_invoking_service()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();

        $methodInvocation = MethodInvoker::createWith($serviceExpectingOneArgument, 'withoutReturnValue', [
            PayloadBuilder::create('name')
        ], true, InMemoryReferenceSearchService::createEmpty());

        $methodInvocation->processMessage(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($serviceExpectingOneArgument->wasCalled(), "Method was not called");
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_not_changing_content_type_of_message_if_message_is_return()
    {
        $serviceExpectingOneArgument = ServiceExpectingMessageAndReturningMessage::create("test");

        $methodInvocation = MethodInvoker::createWith($serviceExpectingOneArgument, 'send', [
            MessageConverterBuilder::create("message")
        ], true, InMemoryReferenceSearchService::createEmpty());

        $this->assertMessages(
            MessageBuilder::withPayload("test")
                ->build(),
            $methodInvocation->processMessage(MessageBuilder::withPayload('some')->build())
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_invoking_service_with_return_value_from_header()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();
        $headerName = 'token';
        $headerValue = '123X';

        $methodInvocation = MethodInvoker::createWith($serviceExpectingOneArgument, 'withReturnValue', [
            HeaderBuilder::create('name', $headerName)
        ], true, InMemoryReferenceSearchService::createEmpty());

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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_if_method_requires_one_argument_and_there_was_not_passed_any_then_use_payload_one_as_default()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();

        $methodInvocation = MethodInvoker::createWith($serviceExpectingOneArgument, 'withReturnValue', [], true, InMemoryReferenceSearchService::createEmpty());

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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_passed_wrong_argument_names()
    {
        $serviceExpectingOneArgument = ServiceExpectingOneArgument::create();

        $this->expectException(InvalidArgumentException::class);

        MethodInvoker::createWith($serviceExpectingOneArgument, 'withoutReturnValue', [
            PayloadBuilder::create('wrongName')
        ], true, InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_invoking_service_with_multiple_not_ordered_arguments()
    {
        $serviceExpectingThreeArgument = ServiceExpectingThreeArguments::create();

        $methodInvocation = MethodInvoker::createWith($serviceExpectingThreeArgument, 'withReturnValue', [
            HeaderBuilder::create('surname', 'personSurname'),
            HeaderBuilder::create('age', 'personAge'),
            PayloadBuilder::create('name'),
        ], true, InMemoryReferenceSearchService::createEmpty());

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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_invoking_with_payload_conversion()
    {
        $methodInvocation = MethodInvoker::createWith(new OrderProcessor(), 'processOrder', [
            PayloadBuilder::create('order')
        ], false, InMemoryReferenceSearchService::createWith([
            AutoCollectionConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([
                new DeserializingConverter()
            ])
        ]));

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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_invoking_with_header_conversion()
    {
        $methodInvocation = MethodInvoker::createWith(new OrderProcessor(), 'buyByName', [
            HeaderBuilder::create("id", "uuid")
        ], false, InMemoryReferenceSearchService::createWith([
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

        $this->assertMessages(
            MessageBuilder::withPayload(OrderConfirmation::createFromUuid(Uuid::fromString($uuid)))
                ->setHeader("uuid", $uuid)
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter("\\" . OrderConfirmation::class)->toString())
                ->build(),
            $replyMessage
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_invoking_with_converter_for_collection_if_types_are_compatible()
    {
        $methodInvocation = MethodInvoker::createWith(new OrderProcessor(), 'buyMultiple', [
            PayloadBuilder::create("ids")
        ], false, InMemoryReferenceSearchService::createWith([
            AutoCollectionConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([
                new StringToUuidConverter()
            ])
        ]));

        $replyMessage = $methodInvocation->processMessage(
            MessageBuilder::withPayload(["fd825894-907c-4c6c-88a9-ae1ecdf3d307", "fd825894-907c-4c6c-88a9-ae1ecdf3d308"])
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter("array<string>")->toString())
                ->build()
        );

        $this->assertMessages(
            MessageBuilder::withPayload([OrderConfirmation::createFromUuid(Uuid::fromString("fd825894-907c-4c6c-88a9-ae1ecdf3d307")), OrderConfirmation::createFromUuid(Uuid::fromString("fd825894-907c-4c6c-88a9-ae1ecdf3d308"))])
                ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter("array<\\" . OrderConfirmation::class . ">")->toString())
                ->build(),
            $replyMessage
        );
    }
}