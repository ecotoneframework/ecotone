<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\ResolveEvents;

use Ecotone\Messaging\Message;
use Ecotone\Modelling\ResolveAggregateEventsService;

/**
 * licence Apache-2.0
 */
final class ResolveMultipleAggregateEventsService implements ResolveAggregateEventsService
{
    public function __construct(
        private ResolveAggregateEventsService $resolveCalledAggregateEventsService,
        private ResolveAggregateEventsService $resolveResultAggregateEventsService,
    ) {
    }

    public function process(Message $message): Message
    {
        $message = $this->resolveCalledAggregateEventsService->process($message);
        $message = $this->resolveResultAggregateEventsService->process($message);

        return $message;
    }
}
