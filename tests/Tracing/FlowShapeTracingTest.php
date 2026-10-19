<?php

declare(strict_types=1);

namespace Test\Ecotone\Tracing;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\Orchestrator;
use Ecotone\Api\Attribute\OrchestratorGateway;
use Ecotone\Api\Attribute\Router;
use Ecotone\Api\Attribute\Splitter;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\InMemorySpanRecorder;
use Ecotone\Api\Tracing\Span;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Test\LicenceTesting;

/**
 * licence Apache-2.0
 * @internal
 */
final class FlowShapeTracingTest extends TracingTestCase
{
    public function test_an_output_channel_chain_traces_every_handler_under_one_trace_with_its_own_endpoint(): void
    {
        $recorder = new InMemorySpanRecorder();
        $orders = new class () {
            #[CommandHandler('order.place', outputChannelName: 'order.validate', endpointId: 'order.placing')]
            public function place(string $orderId): string
            {
                return $orderId;
            }

            #[InternalHandler('order.validate', outputChannelName: 'order.store', endpointId: 'order.validating')]
            public function validate(string $orderId): string
            {
                return $orderId;
            }

            #[InternalHandler('order.store', endpointId: 'order.storing')]
            public function store(string $orderId): void
            {
            }
        };

        self::bootstrap($recorder, [$orders])->sendCommandWithRouting('order.place', '1');

        $this->assertSame(
            ['process class@anonymous::place', 'process class@anonymous::store', 'process class@anonymous::validate', 'send command'],
            self::spanNames($recorder),
        );
        $this->assertSame(
            ['process class@anonymous::place' => 'order.placing', 'process class@anonymous::store' => 'order.storing', 'process class@anonymous::validate' => 'order.validating'],
            self::endpointIdsByHandlerSpan($recorder),
        );
        $this->assertCount(1, self::traceIds($recorder));
    }

    public function test_a_splitter_fans_one_command_out_into_one_handler_span_per_item(): void
    {
        $recorder = new InMemorySpanRecorder();
        $imports = new class () {
            #[CommandHandler('batch.import', outputChannelName: 'batch.split', endpointId: 'batch.importing')]
            public function import(array $items): array
            {
                return $items;
            }

            #[Splitter('batch.split', outputChannelName: 'item.handle')]
            public function split(array $items): array
            {
                return $items;
            }

            #[InternalHandler('item.handle', endpointId: 'item.handling')]
            public function handle(string $item): void
            {
            }
        };

        self::bootstrap($recorder, [$imports])->sendCommandWithRouting('batch.import', ['a', 'b']);

        $this->assertSame(
            ['process class@anonymous::handle', 'process class@anonymous::handle', 'process class@anonymous::import', 'send command'],
            self::spanNames($recorder),
        );
        $this->assertSame(['item.handling', 'item.handling'], self::endpointIdsOfSpansNamed('process class@anonymous::handle', $recorder));
        $this->assertCount(1, self::traceIds($recorder));
    }

    public function test_a_router_leads_the_trace_to_the_handler_it_chose(): void
    {
        $recorder = new InMemorySpanRecorder();
        $shipments = new class () {
            #[CommandHandler('shipment.dispatch', outputChannelName: 'shipment.route', endpointId: 'shipment.dispatching')]
            public function dispatch(string $shipmentId): string
            {
                return $shipmentId;
            }

            #[Router('shipment.route')]
            public function route(string $shipmentId): string
            {
                return 'shipment.express';
            }

            #[InternalHandler('shipment.express', endpointId: 'shipment.expressing')]
            public function express(string $shipmentId): void
            {
            }

            #[InternalHandler('shipment.standard', endpointId: 'shipment.standard.handling')]
            public function standard(string $shipmentId): void
            {
            }
        };

        self::bootstrap($recorder, [$shipments])->sendCommandWithRouting('shipment.dispatch', '1');

        $this->assertSame(
            ['process class@anonymous::dispatch', 'process class@anonymous::express', 'send command'],
            self::spanNames($recorder),
        );
        $this->assertSame(['shipment.expressing'], self::endpointIdsOfSpansNamed('process class@anonymous::express', $recorder));
    }

    public function test_an_orchestrator_gateway_starting_the_flow_traces_each_step_under_one_trace(): void
    {
        $recorder = new InMemorySpanRecorder();
        $steps = new class () {
            #[InternalHandler('approval.validate', endpointId: 'approval.validating')]
            public function validate(string $request): string
            {
                return $request;
            }

            #[InternalHandler('approval.notify', endpointId: 'approval.notifying')]
            public function notify(string $request): string
            {
                return $request;
            }
        };
        $ecotone = self::bootstrap($recorder, [$steps], [ApprovalWorkflow::class], LicenceTesting::VALID_LICENCE);

        $ecotone->getGateway(ApprovalWorkflow::class)->start(['approval.validate', 'approval.notify'], 'request-1');

        $this->assertSame(
            ['process class@anonymous::notify', 'process class@anonymous::validate', 'send orchestrator'],
            self::spanNames($recorder),
        );
        $this->assertSame(
            ['process class@anonymous::notify' => 'approval.notifying', 'process class@anonymous::validate' => 'approval.validating'],
            self::endpointIdsByHandlerSpan($recorder),
        );
        $this->assertCount(1, self::traceIds($recorder));
    }

    public function test_an_orchestrator_reached_from_a_command_handler_continues_that_command_trace(): void
    {
        $recorder = new InMemorySpanRecorder();
        $approvals = new class () {
            #[CommandHandler('approval.request', outputChannelName: 'approval.workflow', endpointId: 'approval.requesting')]
            public function request(string $request): string
            {
                return $request;
            }

            #[Orchestrator('approval.workflow')]
            public function workflow(): array
            {
                return ['approval.validate', 'approval.notify'];
            }

            #[InternalHandler('approval.validate', endpointId: 'approval.validating')]
            public function validate(string $request): string
            {
                return $request;
            }

            #[InternalHandler('approval.notify', endpointId: 'approval.notifying')]
            public function notify(string $request): string
            {
                return $request;
            }
        };

        self::bootstrap($recorder, [$approvals], [], LicenceTesting::VALID_LICENCE)->sendCommandWithRouting('approval.request', 'request-1');

        $this->assertSame(
            ['process class@anonymous::notify', 'process class@anonymous::request', 'process class@anonymous::validate', 'send command'],
            self::spanNames($recorder),
        );
        $this->assertSame(
            ['process class@anonymous::notify' => 'approval.notifying', 'process class@anonymous::request' => 'approval.requesting', 'process class@anonymous::validate' => 'approval.validating'],
            self::endpointIdsByHandlerSpan($recorder),
        );
        $this->assertCount(1, self::traceIds($recorder));
    }

    public function test_a_message_gateway_starting_the_flow_is_the_root_of_the_handler_it_reaches(): void
    {
        $recorder = new InMemorySpanRecorder();
        $notifications = new class () {
            #[InternalHandler('notification.send', endpointId: 'notification.sending')]
            public function send(string $text): void
            {
            }
        };
        $ecotone = self::bootstrap($recorder, [$notifications], [NotificationGateway::class]);

        $ecotone->getGateway(NotificationGateway::class)->send('hello');

        $this->assertSame(['process class@anonymous::send', 'send notification.send'], self::spanNames($recorder));
        $this->assertCount(1, self::traceIds($recorder));
        $this->assertSame(['notification.sending'], self::endpointIdsOfSpansNamed('process class@anonymous::send', $recorder));
    }

    public function test_a_command_bus_called_inside_a_command_handler_nests_the_second_command_under_the_first_handler(): void
    {
        $recorder = new InMemorySpanRecorder();
        $users = new class () {
            #[CommandHandler('user.register', endpointId: 'user.registration')]
            public function register(string $userId, CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('audit.record', $userId);
            }

            #[CommandHandler('audit.record', endpointId: 'audit.recording')]
            public function record(string $userId): void
            {
            }
        };

        self::bootstrap($recorder, [$users])->sendCommandWithRouting('user.register', '1');

        self::getNodeAtTargetedSpan(
            [
                'details' => ['name' => 'send command'],
                'child' => [
                    'details' => ['name' => 'process class@anonymous::register', 'attributes' => [SpanAttribute::ENDPOINT_ID => 'user.registration']],
                    'child' => [
                        'details' => ['name' => 'send command'],
                        'child' => ['details' => ['name' => 'process class@anonymous::record', 'attributes' => [SpanAttribute::ENDPOINT_ID => 'audit.recording']]],
                    ],
                ],
            ],
            self::buildTree($recorder->recordedSpans())
        );
    }

    /**
     * @param object[] $services
     * @param class-string[] $gateways
     */
    private static function bootstrap(InMemorySpanRecorder $recorder, array $services, array $gateways = [], ?string $licenceKey = null): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            [...array_map(fn (object $service): string => $service::class, $services), ...$gateways],
            [...$services, ...self::recordingSpansInto($recorder)],
            [self::tracingConfiguration()],
            licenceKey: $licenceKey,
        );
    }

    /**
     * @return string[]
     */
    private static function spanNames(InMemorySpanRecorder $recorder): array
    {
        $names = array_map(
            fn (string $name): string => preg_replace('/^process class@anonymous\S*::/', 'process class@anonymous::', $name),
            self::spanNamesWithoutFrameworkNoise($recorder->recordedSpans()),
        );
        sort($names);

        return $names;
    }

    /**
     * @return array<string, string>
     */
    private static function endpointIdsByHandlerSpan(InMemorySpanRecorder $recorder): array
    {
        $endpointIds = [];
        foreach ($recorder->recordedSpans() as $span) {
            if (isset($span->attributes[SpanAttribute::ENDPOINT_ID])) {
                $endpointIds[preg_replace('/^process class@anonymous\S*::/', 'process class@anonymous::', $span->name)] = $span->attributes[SpanAttribute::ENDPOINT_ID];
            }
        }
        ksort($endpointIds);

        return $endpointIds;
    }

    /**
     * @return string[]
     */
    private static function endpointIdsOfSpansNamed(string $name, InMemorySpanRecorder $recorder): array
    {
        return array_values(array_map(
            fn (Span $span): string => $span->attributes[SpanAttribute::ENDPOINT_ID] ?? '',
            array_filter($recorder->recordedSpans(), fn (Span $span): bool => preg_replace('/^process class@anonymous\S*::/', 'process class@anonymous::', $span->name) === $name),
        ));
    }

    /**
     * @return string[]
     */
    private static function traceIds(InMemorySpanRecorder $recorder): array
    {
        return array_values(array_unique(array_map(fn (Span $span): string => $span->traceId, $recorder->recordedSpans())));
    }
}

interface ApprovalWorkflow
{
    #[OrchestratorGateway]
    public function start(array $routing, mixed $data): mixed;
}

interface NotificationGateway
{
    #[MessageGateway('notification.send')]
    public function send(string $text): void;
}
