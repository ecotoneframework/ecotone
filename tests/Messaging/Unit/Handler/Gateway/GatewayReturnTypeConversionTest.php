<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Test\StaticMessageBuilder;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use stdClass;
use TypeError;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GatewayReturnTypeConversionTest extends TestCase
{
    public function test_converting_reply_according_to_the_interfaces_declared_return_type(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [UuidGateway::class, EchoMessageHandler::class],
            [new EchoMessageHandler()],
            enabledModules: [],
        );

        $this->assertEquals(
            Uuid::fromString($id = 'e7019549-9733-45a3-b088-783de2b2357f'),
            $ecotone->getGateway(UuidGateway::class)->executeWithPayload($id)
        );
    }

    public function test_not_converting_if_reply_already_has_the_expected_type(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [UuidGateway::class, EchoMessageHandler::class],
            [new EchoMessageHandler()],
            enabledModules: [],
        );

        $uuid = Uuid::fromString('e7019549-9733-45a3-b088-783de2b2357f');

        $this->assertSame($uuid, $ecotone->getGateway(UuidGateway::class)->executeWithPayload($uuid));
    }

    public function test_not_converting_when_the_declared_return_type_is_message(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [EchoMessageGateway::class, EchoMessageHandler::class],
            [new EchoMessageHandler()],
            enabledModules: [],
        );

        $sentMessage = StaticMessageBuilder::withPayload('some')->build();
        $replyMessage = $ecotone->getGateway(EchoMessageGateway::class)->execute($sentMessage);

        $this->assertSame('some', $replyMessage->getPayload());
    }

    public function test_throws_when_no_converter_exists_for_the_declared_reply_type(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [StdClassGateway::class, EchoMessageHandler::class],
            [new EchoMessageHandler()],
            enabledModules: [],
        );

        $this->expectException(TypeError::class);

        $ecotone->getGateway(StdClassGateway::class)->executeWithPayload('something');
    }

    public function test_missing_argument_falls_back_to_the_gateway_methods_declared_default_value(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [DefaultParameterGateway::class, EchoMessageHandler::class],
            [new EchoMessageHandler()],
            enabledModules: [],
        );

        $this->assertSame('default', $ecotone->getGateway(DefaultParameterGateway::class)->executeWithDefault());
    }

    public function test_declaring_an_error_channel_on_a_gateway_that_cannot_return_null_is_rejected_at_bootstrap(): void
    {
        $this->expectException(\Ecotone\Messaging\Support\InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [NonNullableErrorChannelGateway::class, EchoMessageHandler::class],
            [new EchoMessageHandler()],
            enabledModules: [],
        );
    }

    public function test_reply_media_type_can_be_requested_dynamically_per_call(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ReplyMediaTypeGateway::class, EchoMessageHandler::class, ArrayToJsonMediaTypeConverter::class],
            [new EchoMessageHandler(), new ArrayToJsonMediaTypeConverter()],
            enabledModules: [],
        );

        $result = $ecotone->getGateway(ReplyMediaTypeGateway::class)->executeWithPayload(['some'], MediaType::APPLICATION_JSON);

        $this->assertSame('["some"]', $result);
    }

    public function test_generator_reply_without_a_declared_element_type_is_passed_through_untouched(): void
    {
        $handler = new IterableEchoHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [IteratorGateway::class, IterableEchoHandler::class],
            [$handler],
            enabledModules: [],
        );

        $resultSet = [];
        foreach ($ecotone->getGateway(IteratorGateway::class)->executeIteratorWithoutType($expected = [1, 2]) as $item) {
            $resultSet[] = $item;
        }

        $this->assertSame($expected, $resultSet);
    }

    public function test_generator_reply_with_a_scalar_element_type_needs_no_conversion(): void
    {
        $handler = new IterableEchoHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [IteratorGateway::class, IterableEchoHandler::class],
            [$handler],
            enabledModules: [],
        );

        $resultSet = [];
        foreach ($ecotone->getGateway(IteratorGateway::class)->executeIteratorWithScalarType($expected = [1, 2]) as $item) {
            $resultSet[] = $item;
        }

        $this->assertSame($expected, $resultSet);
    }

    public function test_generator_reply_with_a_complex_element_type_needs_no_conversion_when_types_already_match(): void
    {
        $handler = new IterableEchoHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [IteratorGateway::class, IterableEchoHandler::class],
            [$handler],
            enabledModules: [],
        );

        $resultSet = [];
        foreach ($ecotone->getGateway(IteratorGateway::class)->executeWithAdvancedIterator($expected = [new stdClass(), new stdClass()]) as $item) {
            $resultSet[] = $item;
        }

        $this->assertSame($expected, $resultSet);
    }

    public function test_array_reply_elements_are_converted_through_a_real_registered_converter(): void
    {
        $handler = new IterableEchoHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ArrayConvertingIteratorGateway::class, IterableEchoHandler::class, IntToStdClassConverter::class],
            [$handler, new IntToStdClassConverter()],
            enabledModules: [],
        );

        $resultSet = $ecotone->getGateway(ArrayConvertingIteratorGateway::class)->executeIteratorWithConversion([1, 2]);

        $this->assertEquals([IntToStdClassConverter::convertOne(1), IntToStdClassConverter::convertOne(2)], $resultSet);
    }

    public function test_generator_reply_elements_are_converted_through_a_real_registered_converter(): void
    {
        $handler = new GeneratorEchoHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [GeneratorConvertingIteratorGateway::class, GeneratorEchoHandler::class, IntToStdClassConverter::class],
            [$handler, new IntToStdClassConverter()],
            enabledModules: [],
        );

        $resultSet = [];
        foreach ($ecotone->getGateway(GeneratorConvertingIteratorGateway::class)->executeGeneratorWithConversion([1, 2]) as $item) {
            $resultSet[] = $item;
        }

        $this->assertEquals([IntToStdClassConverter::convertOne(1), IntToStdClassConverter::convertOne(2)], $resultSet);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface UuidGateway
{
    #[MessageGateway(EchoMessageHandler::CHANNEL)]
    public function executeWithPayload(mixed $payload): UuidInterface;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface EchoMessageGateway
{
    #[MessageGateway(EchoMessageHandler::CHANNEL)]
    public function execute(Message $message): ?Message;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface StdClassGateway
{
    #[MessageGateway(EchoMessageHandler::CHANNEL)]
    public function executeWithPayload(mixed $payload): stdClass;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface DefaultParameterGateway
{
    #[MessageGateway(EchoMessageHandler::CHANNEL)]
    public function executeWithDefault(mixed $payload = 'default'): string;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface NonNullableErrorChannelGateway
{
    #[MessageGateway(EchoMessageHandler::CHANNEL, errorChannel: 'someErrorChannel')]
    public function executeWithPayload(mixed $payload): string;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface ReplyMediaTypeGateway
{
    #[MessageGateway(EchoMessageHandler::CHANNEL)]
    public function executeWithPayload(mixed $payload, #[Header(MessageHeaders::REPLY_CONTENT_TYPE)] string $replyMediaType): string;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class EchoMessageHandler
{
    public const CHANNEL = 'gatewayReturnType.echo';

    #[InternalHandler(self::CHANNEL)]
    public function handle(Message $message): Message
    {
        return $message;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface IteratorGateway
{
    /**
     * @return iterable<int>
     */
    #[MessageGateway(IterableEchoHandler::CHANNEL)]
    public function executeIteratorWithScalarType(mixed $payload): iterable;

    #[MessageGateway(IterableEchoHandler::CHANNEL)]
    public function executeIteratorWithoutType(mixed $payload): iterable;

    /**
     * @return iterable<int, stdClass>
     */
    #[MessageGateway(IterableEchoHandler::CHANNEL)]
    public function executeWithAdvancedIterator(mixed $payload): iterable;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface ArrayConvertingIteratorGateway
{
    /**
     * @return iterable<stdClass>
     */
    #[MessageGateway(IterableEchoHandler::CHANNEL)]
    public function executeIteratorWithConversion(mixed $payload): iterable;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface GeneratorConvertingIteratorGateway
{
    /**
     * @return iterable<stdClass>
     */
    #[MessageGateway(GeneratorEchoHandler::CHANNEL)]
    public function executeGeneratorWithConversion(mixed $payload): iterable;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class IterableEchoHandler
{
    public const CHANNEL = 'gatewayReturnType.iterable';

    #[InternalHandler(self::CHANNEL)]
    public function handle(mixed $payload): iterable
    {
        return $payload;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GeneratorEchoHandler
{
    public const CHANNEL = 'gatewayReturnType.generator';

    #[InternalHandler(self::CHANNEL)]
    public function handle(mixed $payload): iterable
    {
        foreach ($payload as $item) {
            yield $item;
        }
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class IntToStdClassConverter
{
    #[\Ecotone\Api\Attribute\Converter]
    public function convert(int $value): stdClass
    {
        return self::convertOne($value);
    }

    public static function convertOne(int $value): stdClass
    {
        $result = new stdClass();
        $result->id = $value;

        return $result;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
#[\Ecotone\Api\Attribute\MediaTypeConverter]
final class ArrayToJsonMediaTypeConverter implements \Ecotone\Messaging\Conversion\Converter
{
    public function convert($source, \Ecotone\Messaging\Handler\Type $sourceType, MediaType $sourceMediaType, \Ecotone\Messaging\Handler\Type $targetType, MediaType $targetMediaType)
    {
        return json_encode($source);
    }

    public function matches(\Ecotone\Messaging\Handler\Type $sourceType, MediaType $sourceMediaType, \Ecotone\Messaging\Handler\Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceType->isArrayButNotClassBasedCollection()
            && $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_JSON);
    }
}
