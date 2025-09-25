<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\MethodInvocationException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Ecotone\Test\InMemoryConversionService;
use PHPUnit\Framework\Attributes\CoversClass;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\Order;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderConfirmation;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderProcessor;
use Test\Ecotone\Messaging\Fixture\Handler\ExampleService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingMessageAndReturningMessage;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingThreeArguments;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingTwoArguments;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * Class MethodInvocationTest
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
#[CoversClass(MethodInvoker::class)]
#[CoversClass(PayloadBuilder::class)]
#[CoversClass(HeaderBuilder::class)]
#[CoversClass(MessageConverterBuilder::class)]
class MethodInvokerTest extends MessagingTestCase
{
    public function test_invoking_service()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        PayloadBuilder::create('value'),
                    ])
            )
            ->build();

        $this->assertEquals(
            100,
            $messaging->sendDirectToChannel($inputChannel, 100)
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_not_adjusting_the_content_type_when_message_is_returned()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingMessageAndReturningMessage::create('test'), 'send')
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withMethodParameterConverters([
                        MessageConverterBuilder::create('message'),
                    ])
            )
            ->build();

        $message = MessageBuilder::withPayload('{"body":"test"}')
            ->setContentType(MediaType::createApplicationJson())
            ->build();

        $this->assertEquals(
            MediaType::createApplicationJson(),
            $messaging->sendMessageDirectToChannelWithMessageReply($inputChannel, $message)
                ->getHeaders()
                ->getContentType()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_if_method_requires_one_argument_and_there_was_not_passed_any_then_use_payload_one_as_default()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnMixed')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            100,
            $messaging->sendDirectToChannel($inputChannel, 100)
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_if_method_requires_two_argument_and_there_was_not_passed_any_then_use_payload_and_headers_if_possible_as_default()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingTwoArguments::create(), 'payloadAndHeaders')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            [
                'payload' => 100,
                'message_id' => 'someId',
            ],
            $messaging->sendDirectToChannel($inputChannel, 100, metadata: [
                MessageHeaders::MESSAGE_ID => 'someId',
            ])
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_passed_wrong_argument_names()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withoutReturnValue')
                    ->withMethodParameterConverters([
                        PayloadBuilder::create('wrongName'),
                    ])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_invoking_service_with_multiple_not_ordered_arguments()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingThreeArguments::create(), 'withReturnValue')
                    ->withMethodParameterConverters([
                        HeaderBuilder::create('surname', 'personSurname'),
                        HeaderBuilder::create('age', 'personAge'),
                        PayloadBuilder::create('name'),
                    ])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            'johnybilbo13',
            $messaging->sendDirectToChannel($inputChannel, 'johny', metadata: [
                'personSurname' => 'bilbo',
                'personAge' => 13,
            ])
        );
    }

    public function test_invoking_with_payload_conversion()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new OrderProcessor(), 'processOrder')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendMessageDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload(addslashes(serialize(Order::create('1', 'correct'))))
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build()
        );

        $this->assertEquals(
            OrderConfirmation::fromOrder(Order::create('1', 'correct')),
            $message->getPayload()
        );

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(OrderConfirmation::class),
            $message->getHeaders()->getContentType()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_cannot_convert_to_php_media_type()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new OrderProcessor(), 'processOrder')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->expectException(MethodInvocationException::class);

        try {
            $messaging->sendMessageDirectToChannelWithMessageReply(
                $inputChannel,
                MessageBuilder::withPayload(addslashes(serialize(Order::create('1', 'correct'))))
                    ->setContentType(MediaType::createApplicationXml())
                    ->build()
            );
        } catch (MethodInvocationException $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());

            throw $e;
        }
    }

    public function test_calling_if_media_type_is_incompatible_but_types_are_fine()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ExampleService(), 'receiveString')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendMessageDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload('bla')
                ->setContentType(MediaType::createApplicationXml())
                ->build()
        );

        $this->assertEquals(
            'bla',
            $message->getPayload()
        );

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter('string'),
            $message->getHeaders()->getContentType()
        );
    }

    public function test_calling_if_when_parameter_is_union_type_and_argument_compatible_with_second()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ServiceExpectingOneArgument(), 'withUnionParameter')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendMessageDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload('bla')
                ->setContentType(MediaType::createApplicationXml())
                ->build()
        );

        $this->assertEquals(
            'bla',
            $message->getPayload()
        );
    }

    public function test_invoking_with_conversion_based_on_type_id_when_declaration_is_interface()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ServiceExpectingOneArgument(), 'withInterface')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendMessageDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload($data = '893a660c-0208-4140-8be6-95fb2dcd2fdd')
                ->setHeader(MessageHeaders::TYPE_ID, Uuid::class)
                ->setContentType(MediaType::createApplicationXPHP())
                ->build()
        );

        $this->assertEquals(
            Uuid::fromString($data),
            $message->getPayload()
        );
    }

    public function test_invoking_with_conversion_and_union_type_resolving_type_from_type_header_with_different_media_type()
    {
        $messaging = ComponentTestBuilder::create()
            ->withConverter(
                InMemoryConversionService::createWithConversion(
                    $data = '["893a660c-0208-4140-8be6-95fb2dcd2fdd"]',
                    MediaType::createApplicationJson(),
                    TypeDescriptor::STRING,
                    MediaType::createApplicationXPHP(),
                    TypeDescriptor::ARRAY,
                    $result = ['893a660c-0208-4140-8be6-95fb2dcd2fdd']
                )
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ServiceExpectingOneArgument(), 'withUnionParameterWithArray')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            $result,
            $messaging->sendDirectToChannel(
                $inputChannel,
                $data,
                metadata: [
                    MessageHeaders::TYPE_ID => TypeDescriptor::ARRAY,
                    MessageHeaders::CONTENT_TYPE => MediaType::createApplicationJson()->toString(),
                ]
            )
        );
    }

    public function test_invoking_with_conversion_and_object_type_resolving_type_from_type_header()
    {
        $messaging = ComponentTestBuilder::create()
            ->withConverter(
                InMemoryConversionService::createWithConversion(
                    $data = '893a660c-0208-4140-8be6-95fb2dcd2fdd',
                    MediaType::createApplicationJson(),
                    TypeDescriptor::STRING,
                    MediaType::createApplicationXPHP(),
                    stdClass::class,
                    $result = new stdClass()
                )
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ServiceExpectingOneArgument(), 'withObjectTypeHint')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            $result,
            $messaging->sendDirectToChannel(
                $inputChannel,
                $data,
                metadata: [
                    MessageHeaders::TYPE_ID => stdClass::class,
                    MessageHeaders::CONTENT_TYPE => MediaType::createApplicationJson()->toString(),
                ]
            )
        );
    }

    public function test_throwing_exception_if_deserializing_to_union_without_type_header()
    {
        $messaging = ComponentTestBuilder::create()
            ->withConverter(
                InMemoryConversionService::createWithConversion(
                    $data = '["893a660c-0208-4140-8be6-95fb2dcd2fdd"]',
                    MediaType::createApplicationJson(),
                    TypeDescriptor::STRING,
                    MediaType::createApplicationXPHP(),
                    TypeDescriptor::ARRAY,
                    $result = ['893a660c-0208-4140-8be6-95fb2dcd2fdd']
                )
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ServiceExpectingOneArgument(), 'withUnionParameterWithArray')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->expectException(MethodInvocationException::class);

        try {
            $messaging->sendDirectToChannel(
                $inputChannel,
                $data,
            );
        } catch (MethodInvocationException $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());

            throw $e;
        }
    }

    public function test_invoking_with_header_conversion_for_union_type_parameter()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ServiceExpectingOneArgument(), 'withUnionParameterWithUuid')
                    ->withMethodParameterConverters([
                        HeaderBuilder::create('value', 'uuid'),
                    ])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            $uuid = 'fd825894-907c-4c6c-88a9-ae1ecdf3d307',
            $messaging->sendDirectToChannel(
                $inputChannel,
                metadata: [
                    'uuid' => $uuid,
                ]
            )
        );
    }

    public function test_if_can_not_decide_return_type_make_use_resolved_from_return_value_for_array()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ServiceExpectingOneArgument(), 'withCollectionAndArrayReturnType')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter('array<string>'),
            $messaging->sendDirectToChannelWithMessageReply(
                $inputChannel,
                ['test']
            )->getHeaders()->getContentType()
        );
    }

    public function test_if_can_decide_based_on_return_type_then_should_be_used_for_array()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ServiceExpectingOneArgument(), 'withCollectionAndArrayReturnType')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter('array<stdClass>'),
            $messaging->sendDirectToChannelWithMessageReply(
                $inputChannel,
                [new stdClass()]
            )->getHeaders()->getContentType()
        );
    }

    public function test_given_return_type_is_union_then_should_decide_on_return_type_based_on_return_variable()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new ServiceExpectingOneArgument(), 'withUnionReturnType')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(stdClass::class),
            $messaging->sendDirectToChannelWithMessageReply(
                $inputChannel,
                new stdClass()
            )->getHeaders()->getContentType()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    public function test_invoking_with_converter_for_collection_if_types_are_compatible()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new OrderProcessor(), 'buyMultiple')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertEquals(
            [OrderConfirmation::createFromUuid(Uuid::fromString('fd825894-907c-4c6c-88a9-ae1ecdf3d307')), OrderConfirmation::createFromUuid(Uuid::fromString('fd825894-907c-4c6c-88a9-ae1ecdf3d308'))],
            $messaging->sendDirectToChannel(
                $inputChannel,
                ['fd825894-907c-4c6c-88a9-ae1ecdf3d307', 'fd825894-907c-4c6c-88a9-ae1ecdf3d308']
            )
        );
    }
}
