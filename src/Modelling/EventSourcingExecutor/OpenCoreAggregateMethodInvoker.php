<?php

declare(strict_types=1);

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\EventSourcingHandlerMethod;

/**
 * licence Apache-2.0
 */
final class OpenCoreAggregateMethodInvoker implements AggregateMethodInvoker
{
    public function executeMethod(mixed $aggregate, EventSourcingHandlerMethod $eventSourcingHandler, Message $message): void
    {
        if ($eventSourcingHandler->parametersCount() > 1) {
            throw InvalidArgumentException::create("Using multiple parameters for Event Sourcing Handler: {$eventSourcingHandler} is part of Enterprise features. To use this feature obtain Enterprise.");
        }

        $aggregate->{$eventSourcingHandler->getMethodName()}($message->getPayload());
    }
}
