<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class NonPollableChannelInterceptorTest extends TestCase
{
    public function test_sending_to_direct_channel_inside_command_handler_is_handled_inline(): void
    {
        $service = $this->directChannelService();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$service::class, DirectChannelPayloadForNonPollableChannel::class],
            [$service],
            extensionObjects: [SimpleMessageChannelBuilder::createDirectMessageChannel($service::DIRECT_CHANNEL)],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting($service::TRIGGER_ROUTING_KEY, 'first');

        $this->assertSame(['first'], $service->handledDuringCommandHandling);
        $this->assertSame('handled-first', $service->replyDuringCommandHandling);
    }

    public function test_sending_object_to_direct_channel_keeps_payload_type(): void
    {
        $service = $this->directChannelService();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$service::class, DirectChannelPayloadForNonPollableChannel::class],
            [$service],
            extensionObjects: [SimpleMessageChannelBuilder::createDirectMessageChannel($service::DIRECT_CHANNEL)],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting($service::TRIGGER_OBJECT_ROUTING_KEY, 'second');

        $this->assertSame([DirectChannelPayloadForNonPollableChannel::class], $service->handledPayloadTypes);
    }

    private function directChannelService(): object
    {
        return new class () {
            public const DIRECT_CHANNEL = 'nonPollableDirectChannel';
            public const TRIGGER_ROUTING_KEY = 'nonPollable.trigger';
            public const TRIGGER_OBJECT_ROUTING_KEY = 'nonPollable.triggerObject';

            public array $handled = [];
            public array $handledPayloadTypes = [];
            public array $handledDuringCommandHandling = [];
            public mixed $replyDuringCommandHandling = null;

            #[CommandHandler(self::TRIGGER_ROUTING_KEY)]
            public function trigger(string $payload, #[Reference] MessagingEntrypointService $messagingEntrypoint): void
            {
                $this->replyDuringCommandHandling = $messagingEntrypoint->send($payload, self::DIRECT_CHANNEL);
                $this->handledDuringCommandHandling = $this->handled;
            }

            #[CommandHandler(self::TRIGGER_OBJECT_ROUTING_KEY)]
            public function triggerWithObject(string $payload, #[Reference] MessagingEntrypointService $messagingEntrypoint): void
            {
                $messagingEntrypoint->send(new DirectChannelPayloadForNonPollableChannel($payload), self::DIRECT_CHANNEL);
            }

            #[InternalHandler(inputChannelName: self::DIRECT_CHANNEL)]
            public function receive(mixed $payload): string
            {
                $this->handled[] = is_string($payload) ? $payload : $payload->name;
                $this->handledPayloadTypes[] = get_debug_type($payload);

                return 'handled-' . (is_string($payload) ? $payload : $payload->name);
            }
        };
    }
}

final class DirectChannelPayloadForNonPollableChannel
{
    public function __construct(public string $name)
    {
    }
}
