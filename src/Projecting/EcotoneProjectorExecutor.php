<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Event;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;

use function is_null;

class EcotoneProjectorExecutor implements ProjectorExecutor
{
    public function __construct(
        private MessagingEntrypoint $messagingEntrypoint,
        private MessageHeadersPropagatorInterceptor $messageHeadersPropagatorInterceptor,
        private string $projectionName, // this is required for event stream emitter so it can create a stream with this name
        private MessageProcessor $routerProcessor,
        private ?string $initChannel = null,
        private ?string $deleteChannel = null,
        private ?string $flushChannel = null,
        private bool $isLive = true,
    ) {
    }

    public function project(Event $event, mixed $userState = null): mixed
    {
        $metadata = $event->getMetadata();
        $metadata[ProjectingHeaders::PROJECTION_STATE] = $userState ?? null;
        $metadata[ProjectingHeaders::PROJECTION_EVENT_NAME] = $event->getEventName();
        $metadata[ProjectingHeaders::PROJECTION_NAME] = $this->projectionName;
        $metadata[ProjectingHeaders::PROJECTION_LIVE] = $this->isLive;
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
            $this->messagingEntrypoint->send([], $this->initChannel);
        }
    }

    public function delete(): void
    {
        if ($this->deleteChannel) {
            $this->messagingEntrypoint->send([], $this->deleteChannel);
        }
    }

    public function flush(): void
    {
        if ($this->flushChannel) {
            $this->messagingEntrypoint->send([], $this->flushChannel);
        }
    }
}
