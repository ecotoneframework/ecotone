<?php

declare(strict_types=1);

namespace Ecotone\Test\Flow;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Tracing\SpanAttribute;
use Ecotone\Api\Tracing\SpanKind;

/**
 * licence Apache-2.0
 */
final class DistributedFlowScenarios
{
    /**
     * @return list<FlowScenario>
     */
    public static function all(): array
    {
        return [
            self::distributedCommandConsumedByTheTargetService(),
            self::distributedEventDeliveredToEverySubscribedChannel(),
            self::distributedMessageAnsweredOnItsReplyToChannel(),
        ];
    }

    private static function distributedCommandConsumedByTheTargetService(): FlowScenario
    {
        $tickets = new class () {
            #[Distributed]
            #[CommandHandler('ticket.create', endpointId: 'ticket.creation')]
            public function create(string $description): void
            {
            }
        };

        return new FlowScenario(
            'distributed command consumed by the target service',
            [$tickets::class],
            [$tickets],
            [
                SimpleMessageChannelBuilder::createQueueChannel('distributed_channel'),
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'ticket_service', channelName: 'distributed_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('ticket_service'),
            ],
            [
                FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->getDistributedBus()->sendCommand('ticket_service', 'ticket.create', 'User changed billing address', metadata: $metadata))->leavingWorkPending()
                    ->expect(
                        ExpectedSpan::named('send distributed')->ofKind(SpanKind::Producer)->containing(
                            ExpectedSpan::named('send distributed_channel')->ofKind(SpanKind::Producer),
                        ),
                    )
                    ->expectNoSpanNamed('process class@anonymous::create')
                    ->withNoOtherSpans(),
                FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('distributed_channel', ExecutionPollingMetadata::createWithTestingSetup()))
                    ->expect(
                        ExpectedSpan::named('receive distributed_channel')->ofKind(SpanKind::Consumer)->containing(
                            ExpectedSpan::named('send command')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'ticket.create'])->containing(
                                ExpectedSpan::named('process class@anonymous::create')->withAttributes([SpanAttribute::ENDPOINT_ID => 'ticket.creation']),
                            ),
                        ),
                    )
                    ->withNoOtherSpans(),
            ],
        );
    }

    private static function distributedEventDeliveredToEverySubscribedChannel(): FlowScenario
    {
        $billing = new class () {
            #[Distributed]
            #[EventHandler('user.billing_changed', endpointId: 'billing.updating')]
            public function update(string $details): void
            {
            }
        };

        return new FlowScenario(
            'distributed event delivered to every subscribed channel',
            [$billing::class],
            [$billing],
            [
                SimpleMessageChannelBuilder::createQueueChannel('billing_events'),
                SimpleMessageChannelBuilder::createQueueChannel('audit_events'),
                DistributedServiceMap::initialize()
                    ->withEventMapping(channelName: 'billing_events', subscriptionKeys: ['user.*'])
                    ->withEventMapping(channelName: 'audit_events', subscriptionKeys: ['order.*']),
                ServiceConfiguration::createWithDefaults()->withServiceName('billing_service'),
            ],
            [
                FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->getDistributedBus()->publishEvent('user.billing_changed', 'User changed billing address', metadata: $metadata))
                    ->expect(ExpectedSpan::named('send distributed')->ofKind(SpanKind::Producer)->containing(ExpectedSpan::named('send billing_events')->ofKind(SpanKind::Producer)))
                    ->expectNoSpanNamed('send audit_events')
                    ->withNoOtherSpans()
                    ->leavingWorkPending(),
                FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('billing_events', ExecutionPollingMetadata::createWithTestingSetup()))
                    ->expect(
                        ExpectedSpan::named('send billing_events')->containing(
                            ExpectedSpan::named('receive billing_events')->ofKind(SpanKind::Consumer)->containing(
                                ExpectedSpan::named('send event')->withAttributes([SpanAttribute::MESSAGING_DESTINATION_NAME => 'user.billing_changed'])->containing(
                                    ExpectedSpan::named('route billing.updating')->containing(
                                        ExpectedSpan::named('process class@anonymous::update')->withAttributes([SpanAttribute::ENDPOINT_ID => 'billing.updating']),
                                    ),
                                ),
                            ),
                        ),
                    ),
            ],
        );
    }

    private static function distributedMessageAnsweredOnItsReplyToChannel(): FlowScenario
    {
        $tickets = new class () {
            #[Distributed]
            #[InternalHandler('ticket.create', endpointId: 'ticket.creation')]
            public function create(string $description): string
            {
                return 'ticket for: ' . $description;
            }

            #[Distributed]
            #[InternalHandler('ticket.created', endpointId: 'ticket.confirmation')]
            public function confirm(string $ticket): void
            {
            }
        };

        return new FlowScenario(
            'distributed message answered on its reply routing key',
            [$tickets::class],
            [$tickets],
            [
                SimpleMessageChannelBuilder::createQueueChannel('distributed_channel'),
                DistributedServiceMap::initialize()->withCommandMapping(targetServiceName: 'ticket_service', channelName: 'distributed_channel'),
                ServiceConfiguration::createWithDefaults()->withServiceName('ticket_service'),
            ],
            [
                FlowStage::when(fn (FlowTestSupport $ecotone, array $metadata) => $ecotone->getDistributedBus()->sendMessage('ticket_service', 'ticket.create', 'Printer is broken', metadata: $metadata, replyRoutingKey: 'ticket.created'))->leavingWorkPending()
                    ->expect(ExpectedSpan::named('send distributed')->ofKind(SpanKind::Producer)->containing(ExpectedSpan::named('send distributed_channel')->ofKind(SpanKind::Producer)))
                    ->withNoOtherSpans(),
                FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('distributed_channel', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1)))->leavingWorkPending()
                    ->expect(
                        ExpectedSpan::named('receive distributed_channel')->ofKind(SpanKind::Consumer)->containing(
                            ExpectedSpan::named('process class@anonymous::create')->withAttributes([SpanAttribute::ENDPOINT_ID => 'ticket.creation']),
                            ExpectedSpan::named('send distributed_channel')->ofKind(SpanKind::Producer),
                        ),
                    )
                    ->withNoOtherSpans(),
                FlowStage::when(fn (FlowTestSupport $ecotone) => $ecotone->run('distributed_channel', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1)))
                    ->expect(
                        ExpectedSpan::named('receive distributed_channel')->ofKind(SpanKind::Consumer)->containing(
                            ExpectedSpan::named('process class@anonymous::create')->withAttributes([SpanAttribute::ENDPOINT_ID => 'ticket.creation']),
                            ExpectedSpan::named('send distributed_channel')->ofKind(SpanKind::Producer)->containing(
                                ExpectedSpan::named('receive distributed_channel')->ofKind(SpanKind::Consumer)->containing(
                                    ExpectedSpan::named('process class@anonymous::confirm')->withAttributes([SpanAttribute::ENDPOINT_ID => 'ticket.confirmation']),
                                ),
                            ),
                        ),
                    ),
            ],
        );
    }
}
