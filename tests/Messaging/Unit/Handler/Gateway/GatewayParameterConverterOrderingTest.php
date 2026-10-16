<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\Message;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GatewayParameterConverterOrderingTest extends TestCase
{
    public function test_a_header_converter_and_a_payload_converter_can_target_different_parameters_of_the_same_gateway_call(): void
    {
        $handler = new CapturingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [SendMailGateway::class, CapturingHandler::class],
            [$handler],
            enabledModules: [],
        );

        $ecotone->getGateway(SendMailGateway::class)->sendMail('123', 'some bla content');

        $this->assertSame('123', $handler->capturedPersonId);
        $this->assertSame('some bla content', $handler->capturedContent);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface SendMailGateway
{
    #[MessageGateway(CapturingHandler::CHANNEL)]
    public function sendMail(#[Header('personId')] string $personId, #[Payload] string $content): void;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class CapturingHandler
{
    public const CHANNEL = 'gatewayParameterConverterOrdering.channel';

    public ?string $capturedPersonId = null;
    public ?string $capturedContent = null;

    #[InternalHandler(self::CHANNEL)]
    public function handle(Message $message): void
    {
        $this->capturedPersonId = $message->getHeaders()->get('personId');
        $this->capturedContent = $message->getPayload();
    }
}
