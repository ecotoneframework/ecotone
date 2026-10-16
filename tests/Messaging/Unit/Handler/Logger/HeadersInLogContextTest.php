<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Config\ConfigurationException;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * licence Apache-2.0
 * @internal
 */
final class HeadersInLogContextTest extends TestCase
{
    public function test_configured_headers_are_in_the_command_handler_log_context(): void
    {
        $logger = $this->recordingLogger();
        $ecotone = $this->bootstrap($logger, [ServiceConfiguration::createWithDefaults()->withHeadersInLogContext(['userId', 'region'])]);

        $ecotone->sendCommandWithRouting('order.place', 'order-1', metadata: ['userId' => 'user-7', 'region' => 'eu', 'secret' => 'hidden']);

        $context = $logger->contextOf('Executing Command Handler');
        self::assertSame('user-7', $context['userId']);
        self::assertSame('eu', $context['region']);
        self::assertArrayNotHasKey('secret', $context);
    }

    public function test_configured_header_is_in_the_event_handler_log_context_when_propagated_from_the_command(): void
    {
        $logger = $this->recordingLogger();
        $ecotone = $this->bootstrap($logger, [ServiceConfiguration::createWithDefaults()->withHeadersInLogContext(['userId'])]);

        $ecotone->sendCommandWithRouting('order.place', 'order-1', metadata: ['userId' => 'user-7']);

        self::assertSame('user-7', $logger->contextOf('Executing Event Handler')['userId']);
    }

    public function test_configured_header_is_in_the_asynchronous_consumption_log_context(): void
    {
        $logger = $this->recordingLogger();
        $ecotone = $this->bootstrap($logger, [ServiceConfiguration::createWithDefaults()->withHeadersInLogContext(['userId'])]);

        $ecotone->sendCommandWithRouting('order.place_async', 'order-1', metadata: ['userId' => 'user-7']);
        $ecotone->run('orders_async', ExecutionPollingMetadata::createWithTestingSetup());

        self::assertSame('user-7', $logger->contextOf('received from Message Channel `orders_async`')['userId']);
    }

    public function test_configured_header_missing_from_the_message_is_left_out_of_the_log_context(): void
    {
        $logger = $this->recordingLogger();
        $ecotone = $this->bootstrap($logger, [ServiceConfiguration::createWithDefaults()->withHeadersInLogContext(['userId'])]);

        $ecotone->sendCommandWithRouting('order.place', 'order-1');

        self::assertArrayNotHasKey('userId', $logger->contextOf('Executing Command Handler'));
    }

    public function test_headers_are_left_out_of_the_log_context_unless_configured(): void
    {
        $logger = $this->recordingLogger();
        $ecotone = $this->bootstrap($logger, []);

        $ecotone->sendCommandWithRouting('order.place', 'order-1', metadata: ['userId' => 'user-7']);

        self::assertArrayNotHasKey('userId', $logger->contextOf('Executing Command Handler'));
    }

    public function test_headers_configured_in_a_service_context_are_in_the_log_context(): void
    {
        $logger = $this->recordingLogger();
        $serviceContext = new class () {
            #[ServiceContext]
            public function serviceConfiguration(): ServiceConfiguration
            {
                return ServiceConfiguration::createWithDefaults()->withHeadersInLogContext(['userId', 'region']);
            }
        };
        $ecotone = $this->bootstrap($logger, [], [$serviceContext]);

        $ecotone->sendCommandWithRouting('order.place', 'order-1', metadata: ['userId' => 'user-7', 'region' => 'eu']);

        $context = $logger->contextOf('Executing Command Handler');
        self::assertSame('user-7', $context['userId']);
        self::assertSame('eu', $context['region']);
    }

    public function test_empty_header_name_is_refused(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('withHeadersInLogContext() was given an empty header name. Pass the exact name of each message header to add to the log context, for example withHeadersInLogContext([\'userId\']), or remove the empty entry.');

        ServiceConfiguration::createWithDefaults()->withHeadersInLogContext(['userId', '']);
    }

    /**
     * @param object[] $extensionObjects
     * @param object[] $serviceContexts
     */
    private function bootstrap(object $logger, array $extensionObjects, array $serviceContexts = []): FlowTestSupport
    {
        $handler = new class () {
            #[CommandHandler('order.place')]
            public function place(string $orderId, EventBus $eventBus): void
            {
                $eventBus->publishWithRouting('order.placed', $orderId);
            }

            #[EventHandler('order.placed')]
            public function whenPlaced(string $orderId): void
            {
            }

            #[Asynchronous('orders_async')]
            #[CommandHandler('order.place_async', endpointId: 'order.place_async.endpoint')]
            public function placeAsync(string $orderId): void
            {
            }
        };

        return EcotoneLite::bootstrapFlowTesting(
            [$handler::class, ...array_map(fn (object $serviceContext): string => $serviceContext::class, $serviceContexts)],
            [$handler, 'logger' => $logger, ...$serviceContexts],
            extensionObjects: [...$extensionObjects, SimpleMessageChannelBuilder::createQueueChannel('orders_async')],
            enabledModules: [],
        );
    }

    private function recordingLogger(): object
    {
        return new class () extends AbstractLogger {
            /** @var array<int, array{message: string, context: array<string, mixed>}> */
            private array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = ['message' => (string) $message, 'context' => $context];
            }

            /** @return array<string, mixed> */
            public function contextOf(string $messageFragment): array
            {
                foreach ($this->records as $record) {
                    if (str_contains($record['message'], $messageFragment)) {
                        return $record['context'];
                    }
                }

                throw new RuntimeException("No log record containing `{$messageFragment}`");
            }
        };
    }
}
