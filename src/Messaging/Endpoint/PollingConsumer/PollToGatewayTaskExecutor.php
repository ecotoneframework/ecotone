<?php

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Handler\NonProxyGateway;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePoller;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;

/**
 * licence Apache-2.0
 */
class PollToGatewayTaskExecutor implements TaskExecutor
{
    public function __construct(
        private MessagePoller $messagePoller,
        private NonProxyGateway $gateway,
        private MessagingEntrypointService $messagingEntrypoint,
        private AsyncHandlerAnnotationRegistry $asyncHandlerAnnotationRegistry,
        private AsyncEndpointAnnotationContext $asyncEndpointAnnotationContext,
    ) {
    }

    public function execute(PollingMetadata $pollingMetadata): void
    {
        try {
            $this->messagingEntrypoint->send([], MessageHeadersPropagatorInterceptor::ENABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT);

            $message = $this->messagePoller->receiveWithTimeout($pollingMetadata);
        } finally {
            $this->messagingEntrypoint->send([], MessageHeadersPropagatorInterceptor::DISABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT);
        }

        if ($message) {
            $routingSlip = $this->resolveRoutingSlip($message);
            if ($routingSlip !== []) {
                $this->asyncEndpointAnnotationContext->setAnnotations(
                    $this->asyncHandlerAnnotationRegistry->getAnnotationsForChannel($routingSlip[0])
                );
            }
            try {
                $this->gateway->execute([
                    MessageBuilder::fromMessage($message)
                        ->setHeader(MessageHeaders::CONSUMER_POLLING_METADATA, $pollingMetadata)
                        ->build(),
                ]);
            } finally {
                $this->asyncEndpointAnnotationContext->clear();
            }
            gc_collect_cycles();
        }
    }

    /**
     * @return string[]
     */
    private function resolveRoutingSlip(Message $message): array
    {
        if (! $message->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP)) {
            return [];
        }

        $routingSlip = $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP);

        return $routingSlip ? explode(',', $routingSlip) : [];
    }
}
