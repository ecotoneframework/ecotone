<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GatewayParameterConverterAttributesTest extends TestCase
{
    public function test_headers_gateway_parameter_maps_array_entries_to_message_headers(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [EchoGateway::class, EchoHandler::class],
            [new EchoHandler(), 'calculatingService' => new CalculatingReferenceService(1)],
            enabledModules: [],
        );

        $message = $ecotone->getGateway(EchoGateway::class)->sendHeaders([
            'token' => 'some',
            'type' => 'someType',
        ]);

        $this->assertSame('some', $message->getHeaders()->get('token'));
        $this->assertSame('someType', $message->getHeaders()->get('type'));
    }

    public function test_headers_gateway_parameter_throws_when_argument_is_not_an_array(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [EchoGateway::class, EchoHandler::class],
            [new EchoHandler(), 'calculatingService' => new CalculatingReferenceService(1)],
            enabledModules: [],
        );

        $this->expectException(InvalidArgumentException::class);

        $ecotone->getGateway(EchoGateway::class)->sendHeaders(123);
    }

    public function test_payload_expression_gateway_parameter_evaluates_against_a_reference_service(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [EchoGateway::class, EchoHandler::class],
            [new EchoHandler(), 'calculatingService' => new CalculatingReferenceService(1)],
            enabledModules: [],
        );

        $message = $ecotone->getGateway(EchoGateway::class)->sendPayloadExpression(1);

        $this->assertSame(2, $message->getPayload());
    }

    public function test_payload_gateway_parameter_resolves_class_type_on_request_reply_gateway(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [EchoGateway::class, EchoHandler::class],
            [new EchoHandler(), 'calculatingService' => new CalculatingReferenceService(1)],
            enabledModules: [],
        );

        $payload = new stdClass();
        $message = $ecotone->getGateway(EchoGateway::class)->sendPayload($payload);

        $this->assertEquals($payload, $message->getPayload());
        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(stdClass::class),
            $message->getHeaders()->getContentType()
        );
    }

    public function test_payload_gateway_parameter_resolves_class_type_on_fire_and_forget_gateway(): void
    {
        $capturingHandler = new CapturingOneWayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [OneWayEchoGateway::class, CapturingOneWayHandler::class],
            [$capturingHandler],
            enabledModules: [],
        );

        $payload = new stdClass();
        $ecotone->getGateway(OneWayEchoGateway::class)->sendPayloadOneWay($payload);

        $this->assertNotNull($capturingHandler->receivedMessage);
        $this->assertEquals($payload, $capturingHandler->receivedMessage->getPayload());
        $this->assertEquals(
            MediaType::createApplicationXPHPWithTypeParameter(stdClass::class),
            $capturingHandler->receivedMessage->getHeaders()->getContentType()
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface EchoGateway
{
    public const CHANNEL = 'gatewayConverterChannel';

    #[MessageGateway(self::CHANNEL)]
    public function sendHeaders(#[Headers] mixed $headers): Message;

    #[MessageGateway(self::CHANNEL)]
    public function sendPayloadExpression(#[Payload("reference('calculatingService').sum(value)")] int $value): Message;

    #[MessageGateway(self::CHANNEL)]
    public function sendPayload(object $value): Message;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface OneWayEchoGateway
{
    public const CHANNEL = 'gatewayConverterOneWayChannel';

    #[MessageGateway(self::CHANNEL)]
    public function sendPayloadOneWay(object $value): void;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class EchoHandler
{
    #[InternalHandler(EchoGateway::CHANNEL)]
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
final class CapturingOneWayHandler
{
    public ?Message $receivedMessage = null;

    #[InternalHandler(OneWayEchoGateway::CHANNEL)]
    public function handle(Message $message): void
    {
        $this->receivedMessage = $message;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class CalculatingReferenceService
{
    public function __construct(private int $addend)
    {
    }

    public function sum(int $value): int
    {
        return $value + $this->addend;
    }
}
