<?php

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
declare(strict_types=1);

namespace Ecotone\Projecting\EventStoreAdapter;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Config\Routing\BusRoutingMap;
use Ecotone\Projecting\ProjectorExecutor;

/**
 * Internal projector that forwards events from event store to a streaming channel.
 *
 * @internal
 */
class EventStoreChannelAdapterProjection implements ProjectorExecutor
{
    /**
     * @param array<string> $eventNames Glob patterns for filtering events (same as distributed bus)
     */
    public function __construct(
        private MessageChannel $outputChannel,
        private MessageBuilder $messageBuilder,
        private array $eventNames = [],
    ) {
    }

    public function project(Event $event, mixed $userState = null, bool $isRebuilding = false): mixed
    {
        if (! empty($this->eventNames)) {
            $eventName = $event->eventName;
            $matched = false;

            foreach ($this->eventNames as $pattern) {
                if (BusRoutingMap::globMatch($pattern, $eventName)) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                return $userState;
            }
        }

        $payload = $event->getPayload();
        $metadata = $event->metadata;

        if (! isset($metadata[\Ecotone\Api\Messaging\MessageHeaders::CONTENT_TYPE])) {
            $metadata[\Ecotone\Api\Messaging\MessageHeaders::CONTENT_TYPE] = 'application/x-php';
        }

        $this->outputChannel->send(
            $this->messageBuilder->withPayload($payload)
                ->setMultipleHeaders($metadata)
                ->build()
        );

        return $userState;
    }

    public function init(): void
    {
        // No initialization needed
    }

    public function delete(): void
    {
        // No deletion needed
    }

    public function flush(mixed $userState, bool $isRebuilding): void
    {
        // No flushing needed
    }

    public function reset(?string $partitionKey = null): void
    {
        // No reset needed
    }
}
