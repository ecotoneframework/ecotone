<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Orchestrator;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;
use Ecotone\Test\Flow\Fixture\FlowApprovalWorkflow;
use Ecotone\Test\Flow\Fixture\FlowNotificationGateway;
use Ecotone\Test\Flow\Fixture\FlowTicketGateway;

/**
 * licence Apache-2.0
 */
final class GatewayFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::messageGatewayStartingTheFlow(),
            self::orchestratorGatewayStartingTheFlow(),
            self::orchestratorReachedFromCommandHandler(),
            self::asynchronousGatewayQueuesTheCall(),
            self::orchestratorWithAnAsynchronousStep(),
        ];
    }

    private static function messageGatewayStartingTheFlow(): FlowScenario
    {
        $notifications = new class () {
            #[InternalHandler('notification.send', endpointId: 'notification.sending')]
            public function send(string $text): void
            {
            }
        };

        return new FlowScenario('message gateway starting the flow', [$notifications::class, FlowNotificationGateway::class], [$notifications], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->getGateway(FlowNotificationGateway::class)->send('hello'))
                ->expect(
                    ExpectedSpan::named('send notification.send')->ofKind(SpanKind::Server)->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'notification.send'])->containing(
                        ExpectedSpan::named('process class@anonymous::send')->withAttributes([SpanAttribute::ENDPOINT_ID => 'notification.sending']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function orchestratorGatewayStartingTheFlow(): FlowScenario
    {
        $steps = self::approvalSteps();

        return new FlowScenario('orchestrator gateway starting the flow', [$steps::class, FlowApprovalWorkflow::class], [$steps], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->getGateway(FlowApprovalWorkflow::class)->start(['approval.validate', 'approval.notify'], 'request-1'))
                ->expect(
                    ExpectedSpan::named('send orchestrator')->ofKind(SpanKind::Server)->containing(
                        ExpectedSpan::named('process class@anonymous::validate')->withAttributes([SpanAttribute::ENDPOINT_ID => 'approval.validating']),
                        ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'approval.notifying']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function orchestratorReachedFromCommandHandler(): FlowScenario
    {
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
        };
        $steps = self::approvalSteps();

        return new FlowScenario('orchestrator reached from a command handler', [$approvals::class, $steps::class], [$approvals, $steps], [], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('approval.request', 'request-1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'approval.request'])->containing(
                        ExpectedSpan::named('process class@anonymous::request')->withAttributes([SpanAttribute::ENDPOINT_ID => 'approval.requesting']),
                        ExpectedSpan::named('process class@anonymous::validate')->withAttributes([SpanAttribute::ENDPOINT_ID => 'approval.validating']),
                        ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'approval.notifying']),
                    ),
                )
                ->withNoOtherSpans(),
        ]);
    }

    private static function asynchronousGatewayQueuesTheCall(): FlowScenario
    {
        $tickets = new class () {
            #[InternalHandler('ticket.create', endpointId: 'ticket.creating')]
            public function create(string $ticket): void
            {
            }
        };

        return new FlowScenario('asynchronous gateway queues the call until its consumer runs', [$tickets::class, FlowTicketGateway::class], [$tickets], [SimpleMessageChannelBuilder::createQueueChannel('tickets')], [
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->getGateway(FlowTicketGateway::class)->create('broken printer'))
                ->expect(ExpectedSpan::named('send ticket.create')->ofKind(SpanKind::Server)->containing(ExpectedSpan::named('send tickets')->ofKind(SpanKind::Producer)))
                ->expectNoSpanNamed('process class@anonymous::create')
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('tickets', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('send tickets')->containing(
                        ExpectedSpan::named('receive tickets')->ofKind(SpanKind::Consumer)->containing(
                            ExpectedSpan::named('process class@anonymous::create')->withAttributes([SpanAttribute::ENDPOINT_ID => 'ticket.creating']),
                        ),
                    ),
                ),
        ]);
    }

    private static function orchestratorWithAnAsynchronousStep(): FlowScenario
    {
        $approvals = new class () {
            #[CommandHandler('approval.request', outputChannelName: 'approval.workflow', endpointId: 'approval.requesting')]
            public function request(string $request): string
            {
                return $request;
            }

            #[Orchestrator('approval.workflow')]
            public function workflow(): array
            {
                return ['approval.validate', 'approval.review', 'approval.notify'];
            }

            #[InternalHandler('approval.validate', endpointId: 'approval.validating')]
            public function validate(string $request): string
            {
                return $request;
            }

            #[Asynchronous('reviews')]
            #[InternalHandler('approval.review', endpointId: 'approval.reviewing')]
            public function review(string $request): string
            {
                return $request;
            }

            #[InternalHandler('approval.notify', endpointId: 'approval.notifying')]
            public function notify(string $request): string
            {
                return $request;
            }
        };

        return new FlowScenario('orchestrator with an asynchronous step', [$approvals::class], [$approvals], [SimpleMessageChannelBuilder::createQueueChannel('reviews')], [
            FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->sendCommandWithRouting('approval.request', 'request-1', metadata: $metadata))
                ->expect(
                    ExpectedSpan::named('send command')->containing(
                        ExpectedSpan::named('process class@anonymous::request'),
                        ExpectedSpan::named('process class@anonymous::validate')->withAttributes([SpanAttribute::ENDPOINT_ID => 'approval.validating']),
                        ExpectedSpan::named('send reviews')->ofKind(SpanKind::Producer),
                    ),
                )
                ->expectNoSpanNamed('process class@anonymous::review', 'process class@anonymous::notify')
                ->withNoOtherSpans()
                ->leavingWorkPending(),
            FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('reviews', ExecutionPollingMetadata::createWithTestingSetup()))
                ->expect(
                    ExpectedSpan::named('send reviews')->containing(
                        ExpectedSpan::named('receive reviews')->ofKind(SpanKind::Consumer)->containing(
                            ExpectedSpan::named('process class@anonymous::review')->withAttributes([SpanAttribute::ENDPOINT_ID => 'approval.reviewing']),
                            ExpectedSpan::named('process class@anonymous::notify')->withAttributes([SpanAttribute::ENDPOINT_ID => 'approval.notifying']),
                        ),
                    ),
                ),
        ]);
    }

    private static function approvalSteps(): object
    {
        return new class () {
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
    }
}
