<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\ResolveEvents;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\ResolveAggregateEventsService;

final class ResolveMultipleAggregateEventsService implements ResolveAggregateEventsService
{
    public function __construct(
        private ResolveAggregateEventsService $resolveCalledAggregateEventsService,
        private ResolveAggregateEventsService $resolveResultAggregateEventsService,
    ) {
    }

    public function resolve(Message $message, array $metadata): Message
    {
        $message = $this->resolveCalledAggregateEventsService->resolve($message, $metadata);
        $message = $this->resolveResultAggregateEventsService->resolve($message, $metadata);

        return MessageBuilder::fromMessage($message)->build();
    }
}
