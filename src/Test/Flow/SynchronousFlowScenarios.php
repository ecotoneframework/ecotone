<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Router;
use Ecotone\Api\Attribute\Splitter;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;

/**
 * licence Apache-2.0
 */
final class SynchronousFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::commandHandler(),
            self::queryHandler(),
            self::outputChannelChain(),
            self::commandSentFromCommandHandler(),
            self::splitter(),
            self::router(),
        ];
    }

    private static function commandHandler(): FlowScenario
    {
        $orders = new class () {
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
            }
        };

        return new FlowScenario('command handler', [$orders::class], [$orders], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->ofKind(SpanKind::Server)->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.place'])->containing(
                        ExpectedSpan::named('process class@anonymous::place')->ofKind(SpanKind::Server)->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placing']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function queryHandler(): FlowScenario
    {
        $orders = new class () {
            #[QueryHandler('order.status', endpointId: 'order.status.reading')]
            public function status(): string
            {
                return 'placed';
            }
        };

        return new FlowScenario('query handler', [$orders::class], [$orders], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendQueryWithRouting('order.status', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send query')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.status'])->containing(
                        ExpectedSpan::named('process class@anonymous::status')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.status.reading']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function outputChannelChain(): FlowScenario
    {
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

        return new FlowScenario('output channel chain of three handlers', [$orders::class], [$orders], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->containing(
                        ExpectedSpan::named('process class@anonymous::place')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placing']),
                        ExpectedSpan::named('process class@anonymous::validate')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.validating']),
                        ExpectedSpan::named('process class@anonymous::store')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.storing']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function commandSentFromCommandHandler(): FlowScenario
    {
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

        return new FlowScenario('command sent from a command handler', [$users::class], [$users], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('user.register', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'user.register'])->containing(
                        ExpectedSpan::named('process class@anonymous::register')->withAttributes([SpanAttribute::ENDPOINT_ID => 'user.registration'])->containing(
                            ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'audit.record'])->containing(
                                ExpectedSpan::named('process class@anonymous::record')->withAttributes([SpanAttribute::ENDPOINT_ID => 'audit.recording']),
                            ),
                        ),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function splitter(): FlowScenario
    {
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

        return new FlowScenario('splitter fans one command out into one handler per item', [$imports::class], [$imports], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('batch.import', ['a', 'b'], metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->containing(
                        ExpectedSpan::named('process class@anonymous::import')->withAttributes([SpanAttribute::ENDPOINT_ID => 'batch.importing']),
                        ExpectedSpan::named('process class@anonymous::handle')->withAttributes([SpanAttribute::ENDPOINT_ID => 'item.handling']),
                        ExpectedSpan::named('process class@anonymous::handle')->withAttributes([SpanAttribute::ENDPOINT_ID => 'item.handling']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function router(): FlowScenario
    {
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

        return new FlowScenario('router leads the command to the handler it chose', [$shipments::class], [$shipments], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('shipment.dispatch', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'shipment.dispatch'])->containing(
                        ExpectedSpan::named('process class@anonymous::dispatch')->withAttributes([SpanAttribute::ENDPOINT_ID => 'shipment.dispatching']),
                        ExpectedSpan::named('process class@anonymous::express')->withAttributes([SpanAttribute::ENDPOINT_ID => 'shipment.expressing']),
                    ),
                )
                ->expectNoSpanNamed('process class@anonymous::standard')
                ->withNoOtherSpans(),
        ]);
    }
}
