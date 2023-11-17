<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\ResolveAggregate;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\ResolveAggregateService;

final class ResolveMultipleAggregatesService implements ResolveAggregateService
{
    public function __construct(
        private ResolveAggregateService $resolveCalledAggregateService,
        private ResolveAggregateService $resolveResultAggregateService,
    ) {
    }

    public function resolve(Message $message, array $metadata): Message
    {
        $message = $this->resolveCalledAggregateService->resolve($message, $metadata);
        $message = $this->resolveResultAggregateService->resolve($message, $metadata);

        return MessageBuilder::fromMessage($message)->build();
    }
}
