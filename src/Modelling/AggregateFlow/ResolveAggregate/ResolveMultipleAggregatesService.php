<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\ResolveAggregate;

use Ecotone\Messaging\Message;
use Ecotone\Modelling\ResolveAggregateService;

/**
 * licence Apache-2.0
 */
final class ResolveMultipleAggregatesService implements ResolveAggregateService
{
    public function __construct(
        private ResolveAggregateService $resolveCalledAggregateService,
        private ResolveAggregateService $resolveResultAggregateService,
    ) {
    }

    public function process(Message $message): Message
    {
        $message = $this->resolveCalledAggregateService->process($message);
        return $this->resolveResultAggregateService->process($message);
    }
}
