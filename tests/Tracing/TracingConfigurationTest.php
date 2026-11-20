<?php

declare(strict_types=1);

namespace Test\Ecotone\Tracing;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Tracing\InMemorySpanRecorder;
use Ecotone\Api\Tracing\SpanAttribute;

/**
 * licence Apache-2.0
 * @internal
 */
final class TracingConfigurationTest extends TracingTestCase
{
    public function test_routed_factory_and_action_record_distinct_generated_endpoint_ids(): void
    {
        $recorder = new InMemorySpanRecorder();
        $order = new #[Aggregate] class () {
            #[Identifier]
            public string $orderId = '';

            #[CommandHandler('order.place')]
            public static function place(array $command): self
            {
                $order = new self();
                $order->orderId = $command['orderId'];

                return $order;
            }

            #[CommandHandler('order.place')]
            public function placeAgain(array $command): void
            {
            }
        };
        EcotoneLite::bootstrapFlowTesting([$order::class], self::recordingSpansInto($recorder), [self::tracingConfiguration()], enabledModules: [])
            ->sendCommandWithRouting('order.place', ['orderId' => '1'])
            ->sendCommandWithRouting('order.place', ['orderId' => '1']);
        $processSpans = array_values(array_filter($recorder->recordedSpans(), fn ($span) => str_starts_with($span->name, 'process ')));

        self::assertCount(2, $processSpans);
        self::assertNotEmpty($processSpans[0]->attributes[SpanAttribute::ENDPOINT_ID]);
        self::assertNotEmpty($processSpans[1]->attributes[SpanAttribute::ENDPOINT_ID]);
        self::assertNotSame($processSpans[0]->attributes[SpanAttribute::ENDPOINT_ID], $processSpans[1]->attributes[SpanAttribute::ENDPOINT_ID]);
    }

    public function test_without_a_configured_recorder_nothing_is_recorded_and_no_trace_carrier_is_stamped(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            public array $headers = [];

            #[Asynchronous('async_channel')]
            #[CommandHandler('user.register', endpointId: 'user.register.endpoint')]
            public function register(string $userId, #[Headers] array $headers): void
            {
                $this->headers = $headers;
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1')
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame([], $recorder->recordedSpans());
        $this->assertArrayNotHasKey(MessageHeaders::TRACEPARENT, $users->headers);
    }

    public function test_with_a_configured_recorder_the_trace_carrier_reaches_the_asynchronous_handler(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            public array $headers = [];

            #[Asynchronous('async_channel')]
            #[CommandHandler('user.register', endpointId: 'user.register.endpoint')]
            public function register(string $userId, #[Headers] array $headers): void
            {
                $this->headers = $headers;
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration(), SimpleMessageChannelBuilder::createQueueChannel('async_channel')],
        )
            ->sendCommandWithRouting('user.register', '1')
            ->run('async_channel', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertMatchesRegularExpression('/^00-[0-9a-f]{32}-[0-9a-f]{16}-01$/', $users->headers[MessageHeaders::TRACEPARENT]);
    }

    public function test_only_the_configured_headers_become_span_attributes(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId): void
            {
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()->withSpanAttributesFromHeaders('tenant')],
        )
            ->sendCommandWithRouting('user.register', '1', metadata: ['tenant' => 'a', 'authorization' => 'Bearer secret']);

        foreach ($recorder->recordedSpans() as $span) {
            $this->assertSame('a', $span->attributes['tenant'], $span->name);
            $this->assertArrayNotHasKey('authorization', $span->attributes, $span->name);
        }
    }

    public function test_a_user_header_named_like_a_framework_attribute_does_not_become_one(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId): void
            {
            }
        };

        EcotoneLite::bootstrapFlowTesting(
            [$users::class],
            [$users, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
        )
            ->sendCommandWithRouting('user.register', '1', metadata: ['ecotone.endpoint.id' => 'forged', 'ecotone.trace.root' => true, 'messaging.system' => 'forged']);

        $spansByName = [];
        foreach ($recorder->recordedSpans() as $span) {
            $spansByName[preg_replace('/^process class@anonymous\\S*::/', 'process class@anonymous::', $span->name)] = $span;
            $this->assertSame('ecotone', $span->attributes['messaging.system'], $span->name);
        }
        $this->assertSame('user.registration', $spansByName['process class@anonymous::register']->attributes['ecotone.endpoint.id'] ?? null);
        $this->assertArrayNotHasKey('ecotone.endpoint.id', $spansByName['send command']->attributes);
        $this->assertArrayNotHasKey('ecotone.trace.root', $spansByName['process class@anonymous::register']->attributes);
    }
}
