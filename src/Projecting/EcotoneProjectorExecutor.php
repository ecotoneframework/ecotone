<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;

use function is_null;

class EcotoneProjectorExecutor implements ProjectorExecutor
{
    public function __construct(
        private MessagingEntrypointService $messagingEntrypoint,
        private MessageHeadersPropagatorInterceptor $messageHeadersPropagatorInterceptor,
        private string $projectionName,
        private MessageProcessor $routerProcessor,
        private LicenceDecider $licenceDecider,
        private ?string $initChannel = null,
        private ?string $deleteChannel = null,
        private ?string $flushChannel = null,
        private bool $isLive = true,
        private ?string $resetChannel = null,
    ) {
    }

    public function project(Event $event, mixed $userState = null, bool $isRebuilding = false): mixed
    {
        $metadata = $event->getMetadata();
        $metadata[ProjectingHeaders::PROJECTION_STATE] = $userState ?? null;
        $metadata[ProjectingHeaders::PROJECTION_EVENT_NAME] = $event->getEventName();
        if ($this->licenceDecider->hasEnterpriseLicence()) {
            $metadata[ProjectingHeaders::PROJECTION_NAME] = $this->projectionName;
        }
        $metadata[ProjectingHeaders::PROJECTION_LIVE] = $this->isLive && ! $isRebuilding;
        $metadata[MessageHeaders::STREAM_BASED_SOURCED] = true; // this one is required for correct header propagation in EventStreamEmitter...
        $metadata[MessageHeaders::REPLY_CHANNEL] = $responseQueue = new QueueChannel('response_channel');

        $requestMessage = MessageBuilder::withPayload($event->getPayload())
            ->setMultipleHeaders($metadata)
            ->build();

        $this->messageHeadersPropagatorInterceptor->storeHeaders(
            function () use ($requestMessage) {
                $this->routerProcessor->process($requestMessage);
            },
            $requestMessage
        );
        $response = $responseQueue->receive();
        $newUserState = $response?->getPayload();

        if (! is_null($newUserState)) {
            return $newUserState;
        } else {
            return $userState;
        }
    }

    public function init(): void
    {
        if ($this->initChannel) {
            $this->messagingEntrypoint->sendWithHeaders([], $this->withProjectionName([]), $this->initChannel);
        }
    }

    public function delete(): void
    {
        if ($this->deleteChannel) {
            $this->messagingEntrypoint->sendWithHeaders([], $this->withProjectionName([]), $this->deleteChannel);
        }
    }

    public function flush(mixed $userState = null): void
    {
        if ($this->flushChannel) {
            $this->messagingEntrypoint->sendWithHeaders([], $this->withProjectionName([
                ProjectingHeaders::PROJECTION_STATE => $userState,
            ]), $this->flushChannel);
        }
    }

    public function reset(?string $partitionKey = null): void
    {
        if ($this->resetChannel) {
            $headers = $this->withProjectionName([]);

            if ($partitionKey !== null) {
                $headers[ProjectingHeaders::REBUILD_PARTITION_KEY] = $partitionKey;

                $decomposed = AggregatePartitionKey::decompose($partitionKey);
                if ($decomposed !== null) {
                    $headers[MessageHeaders::EVENT_AGGREGATE_TYPE] = $decomposed['aggregateType'];
                    $headers[MessageHeaders::EVENT_AGGREGATE_ID] = $decomposed['aggregateId'];
                }
            }

            $this->messagingEntrypoint->sendWithHeaders([], $headers, $this->resetChannel);
        }
    }

    private function withProjectionName(array $headers): array
    {
        if ($this->licenceDecider->hasEnterpriseLicence()) {
            $headers[ProjectingHeaders::PROJECTION_NAME] = $this->projectionName;
        }
        return $headers;
    }
}
