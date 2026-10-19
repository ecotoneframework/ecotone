<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Test\Flow\Fixture\FlowAudited;

/**
 * licence Apache-2.0
 */
final class InterceptorFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::interceptorSendingACommandBeforeTheHandler(),
        ];
    }

    private static function interceptorSendingACommandBeforeTheHandler(): FlowScenario
    {
        $orders = new class () {
            #[FlowAudited]
            #[CommandHandler('order.place', endpointId: 'order.placing')]
            public function place(string $orderId): void
            {
            }

            #[CommandHandler('audit.record', endpointId: 'audit.recording')]
            public function record(string $orderId): void
            {
            }
        };
        $auditing = new class () {
            #[Before(pointcut: FlowAudited::class)]
            public function audit(string $orderId, #[Reference] CommandBus $commandBus): void
            {
                $commandBus->sendWithRouting('audit.record', $orderId);
            }
        };

        return new FlowScenario('interceptor sending a command before the handler', [$orders::class, $auditing::class, FlowAudited::class], [$orders, $auditing], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('order.place', '1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'order.place'])->containing(
                        ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'audit.record'])->containing(
                            ExpectedSpan::named('process class@anonymous::record')->withAttributes([SpanAttribute::ENDPOINT_ID => 'audit.recording']),
                        ),
                        ExpectedSpan::named('process class@anonymous::place')->withAttributes([SpanAttribute::ENDPOINT_ID => 'order.placing']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }
}
