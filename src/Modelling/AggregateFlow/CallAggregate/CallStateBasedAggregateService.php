<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\CallAggregate;

use Ecotone\Messaging\Message;
use Ecotone\Modelling\CallAggregateService;

/**
 * licence Apache-2.0
 */
final class CallStateBasedAggregateService implements CallAggregateService
{
    public function __construct(
        private AggregateMethodInvoker $aggregateMethodInvoker,
    ) {
    }

    public function call(Message $message): ?Message
    {
        return $this->aggregateMethodInvoker->execute($message)?->build();
    }
}
