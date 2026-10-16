<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\ErrorMessage;
use Ecotone\Api\Messaging\Future;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ChannelTestSupport;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GatewayReplyAndErrorHandlingTest extends TestCase
{
    public function test_send_only_gateway_invokes_the_handler_without_expecting_a_reply(): void
    {
        $handler = new ReplyGatewayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ReplyGateway::class, ReplyGatewayHandler::class],
            [$handler],
            enabledModules: [],
        );

        $ecotone->getGateway(ReplyGateway::class)->sendOnly(new stdClass());

        $this->assertTrue($handler->sendOnlyCalled);
    }

    public function test_receive_only_gateway_returns_the_handlers_reply(): void
    {
        $handler = new ReplyGatewayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ReplyGateway::class, ReplyGatewayHandler::class],
            [$handler],
            enabledModules: [],
        );

        $this->assertSame('handled', $ecotone->getGateway(ReplyGateway::class)->receiveOnly());
    }

    public function test_a_specific_header_converter_overrides_the_same_key_from_a_headers_array_converter(): void
    {
        $handler = new ReplyGatewayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ReplyGateway::class, ReplyGatewayHandler::class],
            [$handler],
            enabledModules: [],
        );

        $ecotone->getGateway(ReplyGateway::class)->sendWithHeaderOverride('someValue', ['personId' => '123', 'other' => 'data']);

        $this->assertSame('someValue', $handler->lastMessage->getHeaders()->get('personId'));
        $this->assertSame('data', $handler->lastMessage->getHeaders()->get('other'));
    }

    public function test_gateway_expecting_a_reply_cannot_target_a_pollable_channel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [PollableReplyGateway::class],
            [],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel(PollableReplyGateway::CHANNEL)],
            enabledModules: [],
        );
    }

    public function test_send_only_gateway_can_target_a_pollable_channel(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [PollableSendOnlyGateway::class],
            [],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel(PollableSendOnlyGateway::CHANNEL)],
            enabledModules: [],
        );

        $ecotone->getGateway(PollableSendOnlyGateway::class)->send('some');

        $this->assertSame('some', ChannelTestSupport::of($ecotone)->receiveMessageFrom(PollableSendOnlyGateway::CHANNEL)->getPayload());
    }

    public function test_future_reply_resolves_to_the_handlers_direct_return_value(): void
    {
        $handler = new ReplyGatewayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ReplyGateway::class, ReplyGatewayHandler::class],
            [$handler],
            enabledModules: [],
        );

        $future = $ecotone->getGateway(ReplyGateway::class)->futureReceive();

        $this->assertSame('handled', $future->resolve());
    }


    public function test_receive_only_with_nullable_return_type_returns_null_when_no_reply_is_produced(): void
    {
        $handler = new VoidReplyGatewayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [NullableReplyGateway::class, VoidReplyGatewayHandler::class],
            [$handler],
            enabledModules: [],
        );

        $this->assertNull($ecotone->getGateway(NullableReplyGateway::class)->receiveOnly());
    }

    public function test_receive_only_with_non_nullable_return_type_throws_when_no_reply_is_produced(): void
    {
        $handler = new VoidReplyGatewayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ReplyGateway::class, VoidReplyGatewayHandler::class],
            [$handler],
            enabledModules: [],
        );

        $this->expectException(InvalidArgumentException::class);

        $ecotone->getGateway(ReplyGateway::class)->receiveOnly();
    }

    public function test_error_during_handling_is_routed_to_the_gateways_declared_error_channel(): void
    {
        $handler = new ThrowingReplyGatewayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ErrorChannelGateway::class, ThrowingReplyGatewayHandler::class],
            [$handler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel(ErrorChannelGateway::ERROR_CHANNEL)],
            enabledModules: [],
        );

        $ecotone->getGateway(ErrorChannelGateway::class)->receiveOnly();

        $errorMessage = ChannelTestSupport::of($ecotone)->receiveMessageFrom(ErrorChannelGateway::ERROR_CHANNEL);
        $this->assertNotNull($errorMessage);
        $this->assertTrue(ErrorMessage::isErrorMessage($errorMessage));
        $this->assertSame('testing exception', $errorMessage->getHeaders()->get(\Ecotone\Api\Messaging\ErrorContext::EXCEPTION_MESSAGE));
    }

    public function test_error_without_a_declared_error_channel_propagates_the_root_cause_exception(): void
    {
        $handler = new ThrowingReplyGatewayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [ReplyGateway::class, ThrowingReplyGatewayHandler::class],
            [$handler],
            enabledModules: [],
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('testing exception');

        $ecotone->getGateway(ReplyGateway::class)->receiveOnly();
    }

    public function test_around_interceptor_declared_on_the_gateway_method_wraps_the_call(): void
    {
        $handler = new ReplyGatewayHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [InterceptedGateway::class, ReplyGatewayHandler::class, GatewayCallRecorder::class],
            [$handler, new GatewayCallRecorder()],
            enabledModules: [],
        );

        $ecotone->getGateway(InterceptedGateway::class)->sendOnly(new stdClass());

        /** @var GatewayCallRecorder $recorder */
        $recorder = $ecotone->getServiceFromContainer(GatewayCallRecorder::class);
        $this->assertTrue($recorder->wasCalled);
        $this->assertTrue($handler->sendOnlyCalled);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface ReplyGateway
{
    public const CHANNEL = 'replyGateway.channel';

    #[MessageGateway(self::CHANNEL)]
    public function sendOnly(object $value): void;

    #[MessageGateway(self::CHANNEL)]
    public function receiveOnly(): string;

    #[MessageGateway(self::CHANNEL)]
    public function sendWithHeaderOverride(#[Header('personId')] string $value, #[Headers] array $data): void;

    #[MessageGateway(self::CHANNEL)]
    public function futureReceive(): Future;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface NullableReplyGateway
{
    #[MessageGateway(ReplyGateway::CHANNEL)]
    public function receiveOnly(): ?string;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface PollableReplyGateway
{
    public const CHANNEL = 'pollableReplyGateway.channel';

    #[MessageGateway(self::CHANNEL)]
    public function receiveOnly(): string;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface PollableSendOnlyGateway
{
    public const CHANNEL = 'pollableSendOnlyGateway.channel';

    #[MessageGateway(self::CHANNEL)]
    public function send(string $value): void;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface ErrorChannelGateway
{
    public const CHANNEL = 'errorChannelGateway.channel';
    public const ERROR_CHANNEL = 'errorChannelGateway.error';

    #[MessageGateway(self::CHANNEL, errorChannel: self::ERROR_CHANNEL)]
    public function receiveOnly(): ?string;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
interface InterceptedGateway
{
    #[MessageGateway(ReplyGateway::CHANNEL)]
    public function sendOnly(object $value): void;
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ReplyGatewayHandler
{
    public bool $sendOnlyCalled = false;
    public ?\Ecotone\Api\Messaging\Message $lastMessage = null;

    #[InternalHandler(ReplyGateway::CHANNEL)]
    public function handle(\Ecotone\Api\Messaging\Message $message): string
    {
        $this->sendOnlyCalled = true;
        $this->lastMessage = $message;

        return 'handled';
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class VoidReplyGatewayHandler
{
    #[InternalHandler(ReplyGateway::CHANNEL)]
    public function handle(): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ThrowingReplyGatewayHandler
{
    #[InternalHandler(ReplyGateway::CHANNEL)]
    public function handle(): void
    {
        throw new RuntimeException('testing exception');
    }

    #[InternalHandler(ErrorChannelGateway::CHANNEL)]
    public function handleErrorChannel(): void
    {
        throw new RuntimeException('testing exception');
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class GatewayCallRecorder
{
    public bool $wasCalled = false;

    #[Around(pointcut: InterceptedGateway::class)]
    public function record(MethodInvocation $methodInvocation): mixed
    {
        $this->wasCalled = true;

        return $methodInvocation->proceed();
    }
}
