<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ServiceActivator\InternalHandlerChangingHeaders;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class InternalHandlerChangingHeadersTest extends TestCase
{
    public function test_changing_headers_true_merges_returned_array_into_headers_and_leaves_payload_unchanged_for_next_handler(): void
    {
        $handlerB = new CapturingHandlerB();
        $ecotone = $this->bootstrap([EnrichingHandlerA::class, CapturingHandlerB::class], [new EnrichingHandlerA(), $handlerB]);

        $ecotone->sendDirectToChannel(EnrichingHandlerA::INPUT_CHANNEL, 'hello');

        $this->assertSame('hello', $handlerB->received);
        $this->assertSame('enriched-value', $handlerB->capturedHeader);
    }

    public function test_null_return_with_changing_headers_true_leaves_message_unchanged(): void
    {
        $handlerB = new CapturingHandlerB();
        $ecotone = $this->bootstrap([NullReturningHandlerA::class, CapturingHandlerB::class], [new NullReturningHandlerA(), $handlerB]);

        $ecotone->sendDirectToChannel(NullReturningHandlerA::INPUT_CHANNEL, 'hello');

        $this->assertSame('hello', $handlerB->received);
        $this->assertNull($handlerB->capturedHeader);
    }

    public function test_non_array_return_with_changing_headers_true_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $ecotone = $this->bootstrap([NonArrayReturningHandler::class], [new NonArrayReturningHandler()]);
        $ecotone->sendDirectToChannel(NonArrayReturningHandler::INPUT_CHANNEL, 'hello');
    }

    public function test_changing_headers_true_works_within_asynchronous_chain(): void
    {
        $handlerB = new CapturingHandlerB();
        $ecotone = $this->bootstrap(
            [AsyncEnrichingHandlerA::class, CapturingHandlerB::class],
            [new AsyncEnrichingHandlerA(), $handlerB],
            [SimpleMessageChannelBuilder::createQueueChannel(AsyncEnrichingHandlerA::ASYNC_CHANNEL)],
        );

        $ecotone->sendDirectToChannel(AsyncEnrichingHandlerA::INPUT_CHANNEL, 'hello');
        $this->assertNull($handlerB->received);

        $ecotone->run(AsyncEnrichingHandlerA::ASYNC_CHANNEL, ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame('hello', $handlerB->received);
        $this->assertSame('enriched-value', $handlerB->capturedHeader);
    }

    public function test_changing_headers_true_overwrites_matching_keys_and_preserves_other_existing_headers(): void
    {
        $handlerB = new CapturingHandlerB();
        $ecotone = $this->bootstrap([EnrichingHandlerA::class, CapturingHandlerB::class], [new EnrichingHandlerA(), $handlerB]);

        $ecotone->sendMessageDirectToChannel(
            EnrichingHandlerA::INPUT_CHANNEL,
            MessageBuilder::withPayload('hello')
                ->setHeader('enrichedHeader', 'original-value')
                ->setHeader('preserveMe', 'still-here')
                ->build(),
        );

        $this->assertSame('enriched-value', $handlerB->capturedHeader);
        $this->assertSame('still-here', $handlerB->preservedHeader);
    }

    public function test_default_changing_headers_false_still_replaces_payload(): void
    {
        $handler = new DefaultBehaviorHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting([DefaultBehaviorHandler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $result = $ecotone->sendDirectToChannel(DefaultBehaviorHandler::INPUT_CHANNEL, 'hello');

        $this->assertSame(['replaced' => 'hello'], $result);
    }

    public function test_without_licence_changing_headers_true_throws_licensing_exception_naming_class_and_method(): void
    {
        $this->expectException(LicensingException::class);
        $this->expectExceptionMessageMatches('/EnrichingHandlerA::enrich/');

        EcotoneLite::bootstrapFlowTesting([EnrichingHandlerA::class, CapturingHandlerB::class], [new EnrichingHandlerA(), new CapturingHandlerB()], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    private function bootstrap(array $classesToResolve, array $services, array $extensionObjects = [])
    {
        return EcotoneLite::bootstrapFlowTesting(
            $classesToResolve,
            $services,
            ServiceConfiguration::createWithDefaults()
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
                ->withExtensionObjects($extensionObjects)
                ->withEnabledModulePackageNames([]),
        );
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class EnrichingHandlerA
{
    public const INPUT_CHANNEL = 'internalHandlerChangingHeaders.a.input';
    public const OUTPUT_CHANNEL = 'internalHandlerChangingHeaders.b.input';

    #[InternalHandler(inputChannelName: self::INPUT_CHANNEL, outputChannelName: self::OUTPUT_CHANNEL, endpointId: 'enriching-handler-a', changingHeaders: true)]
    public function enrich(): array
    {
        return ['enrichedHeader' => 'enriched-value'];
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class NullReturningHandlerA
{
    public const INPUT_CHANNEL = 'internalHandlerChangingHeaders.nullReturn.input';

    #[InternalHandler(inputChannelName: self::INPUT_CHANNEL, outputChannelName: EnrichingHandlerA::OUTPUT_CHANNEL, endpointId: 'null-returning-handler-a', changingHeaders: true)]
    public function enrich(): ?array
    {
        return null;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class NonArrayReturningHandler
{
    public const INPUT_CHANNEL = 'internalHandlerChangingHeaders.nonArray.input';

    #[InternalHandler(inputChannelName: self::INPUT_CHANNEL, endpointId: 'non-array-returning-handler', changingHeaders: true)]
    public function enrich(): mixed
    {
        return 'not-an-array';
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class AsyncEnrichingHandlerA
{
    public const INPUT_CHANNEL = 'internalHandlerChangingHeaders.async.input';
    public const ASYNC_CHANNEL = 'internalHandlerChangingHeaders.async.channel';

    #[Asynchronous(self::ASYNC_CHANNEL)]
    #[InternalHandler(inputChannelName: self::INPUT_CHANNEL, outputChannelName: EnrichingHandlerA::OUTPUT_CHANNEL, endpointId: 'async-enriching-handler-a', changingHeaders: true)]
    public function enrich(): array
    {
        return ['enrichedHeader' => 'enriched-value'];
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class CapturingHandlerB
{
    public mixed $received = null;
    public mixed $capturedHeader = null;
    public mixed $preservedHeader = null;

    #[InternalHandler(inputChannelName: EnrichingHandlerA::OUTPUT_CHANNEL, endpointId: 'capturing-handler-b')]
    public function handle(
        mixed $payload,
        #[Header('enrichedHeader')] mixed $enrichedHeader = null,
        #[Header('preserveMe')] mixed $preserveMe = null,
    ): void {
        $this->received = $payload;
        $this->capturedHeader = $enrichedHeader;
        $this->preservedHeader = $preserveMe;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class DefaultBehaviorHandler
{
    public const INPUT_CHANNEL = 'internalHandlerChangingHeaders.default.input';

    #[InternalHandler(inputChannelName: self::INPUT_CHANNEL, endpointId: 'default-behavior-handler')]
    public function handle(string $payload): array
    {
        return ['replaced' => $payload];
    }
}
