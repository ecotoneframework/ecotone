<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Handler\MethodInvocationException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\Order;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderConfirmation;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderProcessor;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class MethodArgumentResolutionTest extends TestCase
{
    public function test_single_unannotated_parameter_defaults_to_the_payload(): void
    {
        $ecotone = $this->bootstrap();

        $this->assertSame(
            100,
            $ecotone->sendDirectToChannel(MethodResolutionHandler::PAYLOAD_ONLY_CHANNEL, 100)
        );
    }

    public function test_two_unannotated_parameters_default_to_payload_and_headers(): void
    {
        $ecotone = $this->bootstrap();

        $this->assertSame(
            ['payload' => 100, 'message_id' => 'someId'],
            $ecotone->sendDirectToChannel(MethodResolutionHandler::PAYLOAD_AND_HEADERS_CHANNEL, 100, metadata: [
                MessageHeaders::MESSAGE_ID => 'someId',
            ])
        );
    }

    public function test_explicit_header_and_payload_converters_can_be_declared_in_any_order(): void
    {
        $ecotone = $this->bootstrap();

        $this->assertSame(
            'johnybilbo13',
            $ecotone->sendDirectToChannel(MethodResolutionHandler::THREE_ARGUMENTS_CHANNEL, 'johny', metadata: [
                'personSurname' => 'bilbo',
                'personAge' => 13,
            ])
        );
    }

    public function test_deserializes_php_serialized_payload_into_the_declared_class(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendMessageDirectToChannelWithMessageReply(
            MethodResolutionHandler::ORDER_CHANNEL,
            MessageBuilder::withPayload(addslashes(serialize(Order::create('1', 'correct'))))
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build()
        );

        $this->assertEquals(OrderConfirmation::fromOrder(Order::create('1', 'correct')), $message->getPayload());
        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(OrderConfirmation::class),
            $message->getHeaders()->getContentType()
        );
    }

    public function test_throws_when_message_cannot_be_converted_to_the_declared_php_type(): void
    {
        $ecotone = $this->bootstrap();

        $this->expectException(MethodInvocationException::class);

        try {
            $ecotone->sendMessageDirectToChannelWithMessageReply(
                MethodResolutionHandler::ORDER_CHANNEL,
                MessageBuilder::withPayload(addslashes(serialize(Order::create('1', 'correct'))))
                    ->setContentType(MediaType::createApplicationXml())
                    ->build()
            );
        } catch (MethodInvocationException $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());

            throw $e;
        }
    }

    public function test_string_payload_passes_through_unchanged_when_declared_type_needs_no_real_conversion(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendMessageDirectToChannelWithMessageReply(
            MethodResolutionHandler::STRING_CHANNEL,
            MessageBuilder::withPayload('bla')
                ->setContentType(MediaType::createApplicationXml())
                ->build()
        );

        $this->assertSame('bla', $message->getPayload());
        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter('string'),
            $message->getHeaders()->getContentType()
        );
    }

    public function test_union_typed_parameter_resolves_to_the_compatible_member_type(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendMessageDirectToChannelWithMessageReply(
            MethodResolutionHandler::UNION_CHANNEL,
            MessageBuilder::withPayload('bla')
                ->setContentType(MediaType::createApplicationXml())
                ->build()
        );

        $this->assertSame('bla', $message->getPayload());
    }

    public function test_ambiguous_array_docblock_return_type_resolves_element_type_from_returned_strings(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendDirectToChannelWithMessageReply(MethodResolutionHandler::COLLECTION_CHANNEL, ['test']);

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter('array<string>'),
            $message->getHeaders()->getContentType()
        );
    }

    public function test_ambiguous_array_docblock_return_type_resolves_element_type_from_returned_objects(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendDirectToChannelWithMessageReply(MethodResolutionHandler::COLLECTION_CHANNEL, [new stdClass()]);

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter('array<stdClass>'),
            $message->getHeaders()->getContentType()
        );
    }

    public function test_union_return_type_media_header_is_decided_from_the_actual_returned_value(): void
    {
        $ecotone = $this->bootstrap();

        $message = $ecotone->sendDirectToChannelWithMessageReply(MethodResolutionHandler::UNION_RETURN_CHANNEL, new stdClass());

        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(stdClass::class),
            $message->getHeaders()->getContentType()
        );
    }

    public function test_converts_a_collection_of_identifiers_into_a_collection_of_confirmations(): void
    {
        $ecotone = $this->bootstrap();

        $this->assertEquals(
            [
                OrderConfirmation::createFromUuid(Uuid::fromString('fd825894-907c-4c6c-88a9-ae1ecdf3d307')),
                OrderConfirmation::createFromUuid(Uuid::fromString('fd825894-907c-4c6c-88a9-ae1ecdf3d308')),
            ],
            $ecotone->sendDirectToChannel(
                MethodResolutionHandler::MULTIPLE_ORDERS_CHANNEL,
                ['fd825894-907c-4c6c-88a9-ae1ecdf3d307', 'fd825894-907c-4c6c-88a9-ae1ecdf3d308']
            )
        );
    }

    private function bootstrap()
    {
        return EcotoneLite::bootstrapFlowTesting(
            [MethodResolutionHandler::class],
            [new MethodResolutionHandler()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class MethodResolutionHandler
{
    public const PAYLOAD_ONLY_CHANNEL = 'methodResolution.payloadOnly';
    public const PAYLOAD_AND_HEADERS_CHANNEL = 'methodResolution.payloadAndHeaders';
    public const THREE_ARGUMENTS_CHANNEL = 'methodResolution.threeArguments';
    public const ORDER_CHANNEL = 'methodResolution.order';
    public const STRING_CHANNEL = 'methodResolution.string';
    public const UNION_CHANNEL = 'methodResolution.union';
    public const COLLECTION_CHANNEL = 'methodResolution.collection';
    public const UNION_RETURN_CHANNEL = 'methodResolution.unionReturn';
    public const MULTIPLE_ORDERS_CHANNEL = 'methodResolution.multipleOrders';

    #[InternalHandler(self::PAYLOAD_ONLY_CHANNEL)]
    public function payloadOnly(mixed $value): mixed
    {
        return $value;
    }

    #[InternalHandler(self::PAYLOAD_AND_HEADERS_CHANNEL)]
    public function payloadAndHeaders(mixed $payload, #[Headers] array $headers): array
    {
        return [
            'payload' => $payload,
            'message_id' => $headers[MessageHeaders::MESSAGE_ID],
        ];
    }

    #[InternalHandler(self::THREE_ARGUMENTS_CHANNEL)]
    public function threeArguments(
        #[Header('personSurname')] string $surname,
        #[Header('personAge')] int $age,
        #[Payload] string $name,
    ): string {
        return $name . $surname . $age;
    }

    #[InternalHandler(self::ORDER_CHANNEL)]
    public function processOrder(Order $order): OrderConfirmation
    {
        return (new OrderProcessor())->processOrder($order);
    }

    #[InternalHandler(self::STRING_CHANNEL)]
    public function receiveString(string $payload): string
    {
        return $payload;
    }

    #[InternalHandler(self::UNION_CHANNEL)]
    public function receiveUnion(stdClass|string $value): stdClass|string
    {
        return $value;
    }

    /**
     * @return string[]|stdClass[]
     */
    #[InternalHandler(self::COLLECTION_CHANNEL)]
    public function receiveCollection(array $value): array
    {
        return $value;
    }

    #[InternalHandler(self::UNION_RETURN_CHANNEL)]
    public function returnUnion(mixed $value): MethodResolutionHandler|stdClass
    {
        return $value;
    }

    #[InternalHandler(self::MULTIPLE_ORDERS_CHANNEL)]
    public function buyMultiple(array $value): array
    {
        return (new OrderProcessor())->buyMultiple(array_map(static fn (string $id) => Uuid::fromString($id), $value));
    }
}
