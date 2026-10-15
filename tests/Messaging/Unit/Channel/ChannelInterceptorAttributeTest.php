<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Api\Attribute\ChannelInterceptor;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ChannelInterceptorAttributeTest extends TestCase
{
    public function test_interceptor_runs_on_a_synchronous_direct_channel_send(): void
    {
        $interceptor = new UppercasingInterceptor();
        $handler = new CapturingHandler();
        $ecotone = $this->bootstrap([UppercasingInterceptor::class, CapturingHandler::class], [$interceptor, $handler]);

        $ecotone->sendDirectToChannel(CapturingHandler::CHANNEL, 'hello');

        $this->assertSame('HELLO', $handler->received);
    }

    public function test_interceptor_runs_before_a_message_is_enqueued_to_an_async_channel(): void
    {
        $interceptor = new UppercasingInterceptor();
        $handler = new CapturingHandler();
        $ecotone = $this->bootstrap(
            [UppercasingInterceptor::class, AsyncCapturingHandler::class],
            [$interceptor, new AsyncCapturingHandler()],
        );

        $ecotone->sendCommandWithRouting(AsyncCapturingHandler::ROUTING_KEY, 'hello');

        $queuedMessage = $ecotone->getMessageChannel(AsyncCapturingHandler::CHANNEL)->receive();
        $this->assertSame('HELLO', $queuedMessage->getPayload());
    }

    public function test_interceptor_runs_before_a_message_is_enqueued_and_is_observed_by_the_handler_after_run(): void
    {
        $handler = new AsyncCapturingHandler();
        $ecotone = $this->bootstrap(
            [UppercasingInterceptor::class, AsyncCapturingHandler::class],
            [new UppercasingInterceptor(), $handler],
        );

        $ecotone->sendCommandWithRouting(AsyncCapturingHandler::ROUTING_KEY, 'hello');
        $ecotone->run(AsyncCapturingHandler::CHANNEL, ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame('HELLO', $handler->received);
    }

    public function test_header_enrichment_with_change_headers_true(): void
    {
        $interceptor = new HeaderEnrichingInterceptor();
        $handler = new CapturingHandler();
        $ecotone = $this->bootstrap([HeaderEnrichingInterceptor::class, CapturingHandler::class], [$interceptor, $handler]);

        $ecotone->sendDirectToChannel(CapturingHandler::CHANNEL, 'hello');

        $this->assertSame('hello', $handler->received);
        $this->assertSame('enriched-value', $handler->capturedHeader);
    }

    public function test_two_interceptors_on_the_same_channel_run_in_precedence_order(): void
    {
        $handler = new OrderCapturingHandler();
        $ecotone = $this->bootstrap(
            [LowPrecedenceInterceptor::class, HighPrecedenceInterceptor::class, OrderCapturingHandler::class],
            [new LowPrecedenceInterceptor(), new HighPrecedenceInterceptor(), $handler],
        );

        $ecotone->sendDirectToChannel(OrderCapturingHandler::CHANNEL, []);

        $this->assertSame(['high', 'low'], $handler->received);
    }

    public function test_an_interceptor_for_one_channel_does_not_touch_another_channel(): void
    {
        $handler = new TwoChannelHandler();
        $ecotone = $this->bootstrap(
            [ChannelAOnlyInterceptor::class, TwoChannelHandler::class],
            [new ChannelAOnlyInterceptor(), $handler],
        );

        $ecotone->sendDirectToChannel(TwoChannelHandler::CHANNEL_A, 'hello');
        $ecotone->sendDirectToChannel(TwoChannelHandler::CHANNEL_B, 'hello');

        $this->assertSame('HELLO', $handler->receivedFromA);
        $this->assertSame('hello', $handler->receivedFromB);
    }

    public function test_parameter_converters_work_in_the_interceptor_method(): void
    {
        $interceptor = new ReferenceAndHeaderUsingInterceptor();
        $handler = new CapturingHandler();
        $ecotone = $this->bootstrap(
            [ReferenceAndHeaderUsingInterceptor::class, CapturingHandler::class, UppercaseService::class],
            [$interceptor, $handler, new UppercaseService()],
        );

        $ecotone->sendMessageDirectToChannel(
            CapturingHandler::CHANNEL,
            \Ecotone\Messaging\Support\MessageBuilder::withPayload('hello')
                ->setHeader('suffix', '!')
                ->build(),
        );

        $this->assertSame('HELLO!', $handler->received);
    }

    public function test_without_licence_it_throws_licensing_exception_naming_the_class_and_method(): void
    {
        $this->expectException(LicensingException::class);
        $this->expectExceptionMessageMatches('/UppercasingInterceptor::intercept/');

        EcotoneLite::bootstrapFlowTesting(
            [UppercasingInterceptor::class, CapturingHandler::class],
            [new UppercasingInterceptor(), new CapturingHandler()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );
    }

    private function bootstrap(array $classesToResolve, array $services)
    {
        return EcotoneLite::bootstrapFlowTesting(
            $classesToResolve,
            $services,
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel(AsyncCapturingHandler::CHANNEL),
                ])
                    ->withEnabledModulePackageNames([]),
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class CapturingHandler
{
    public const CHANNEL = 'channelInterceptor.capturing';

    public mixed $received = null;
    public mixed $capturedHeader = null;

    #[InternalHandler(self::CHANNEL)]
    public function handle(mixed $payload, #[Header('enrichedHeader')] mixed $enrichedHeader = null): void
    {
        $this->received = $payload;
        $this->capturedHeader = $enrichedHeader;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class AsyncCapturingHandler
{
    public const CHANNEL = 'channelInterceptor.async';
    public const ROUTING_KEY = 'channelInterceptor.async.handle';

    public mixed $received = null;

    #[\Ecotone\Api\Attribute\Asynchronous(self::CHANNEL)]
    #[\Ecotone\Api\Attribute\CommandHandler(self::ROUTING_KEY, 'channelInterceptorAsyncHandler')]
    public function handle(mixed $payload): void
    {
        $this->received = $payload;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class OrderCapturingHandler
{
    public const CHANNEL = 'channelInterceptor.order';

    public array $received = [];

    #[InternalHandler(self::CHANNEL)]
    public function handle(mixed $payload, #[Header('order')] array $order = []): void
    {
        $this->received = $order;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class TwoChannelHandler
{
    public const CHANNEL_A = 'channelInterceptor.a';
    public const CHANNEL_B = 'channelInterceptor.b';

    public mixed $receivedFromA = null;
    public mixed $receivedFromB = null;

    #[InternalHandler(self::CHANNEL_A)]
    public function handleA(mixed $payload): void
    {
        $this->receivedFromA = $payload;
    }

    #[InternalHandler(self::CHANNEL_B)]
    public function handleB(mixed $payload): void
    {
        $this->receivedFromB = $payload;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class UppercasingInterceptor
{
    #[ChannelInterceptor(CapturingHandler::CHANNEL)]
    public function interceptCapturing(#[\Ecotone\Api\Attribute\Payload] string $payload): string
    {
        return strtoupper($payload);
    }

    #[ChannelInterceptor(AsyncCapturingHandler::CHANNEL)]
    public function interceptAsync(#[\Ecotone\Api\Attribute\Payload] string $payload): string
    {
        return strtoupper($payload);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class HeaderEnrichingInterceptor
{
    #[ChannelInterceptor(CapturingHandler::CHANNEL, changeHeaders: true)]
    public function intercept(): array
    {
        return ['enrichedHeader' => 'enriched-value'];
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class HighPrecedenceInterceptor
{
    #[ChannelInterceptor(OrderCapturingHandler::CHANNEL, changeHeaders: true, precedence: 2)]
    public function intercept(#[Header('order')] array $order = []): array
    {
        $order[] = 'high';

        return ['order' => $order];
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class LowPrecedenceInterceptor
{
    #[ChannelInterceptor(OrderCapturingHandler::CHANNEL, changeHeaders: true, precedence: 1)]
    public function intercept(#[Header('order')] array $order = []): array
    {
        $order[] = 'low';

        return ['order' => $order];
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ChannelAOnlyInterceptor
{
    #[ChannelInterceptor(TwoChannelHandler::CHANNEL_A)]
    public function intercept(#[\Ecotone\Api\Attribute\Payload] string $payload): string
    {
        return strtoupper($payload);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class UppercaseService
{
    public function convert(string $value): string
    {
        return strtoupper($value);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ReferenceAndHeaderUsingInterceptor
{
    #[ChannelInterceptor(CapturingHandler::CHANNEL)]
    public function intercept(#[\Ecotone\Api\Attribute\Payload] string $payload, #[Header('suffix')] string $suffix, #[Reference] UppercaseService $service): string
    {
        return $service->convert($payload) . $suffix;
    }
}
