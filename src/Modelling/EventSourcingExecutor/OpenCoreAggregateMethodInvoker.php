<?php

declare(strict_types=1);

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\EventSourcingHandlerMethod;

/**
 * licence Apache-2.0
 */
final class OpenCoreAggregateMethodInvoker implements AggregateMethodInvoker
{
    public function executeMethod(mixed $aggregate, InterfaceToCall $eventSourcingHandlerInterface, EventSourcingHandlerMethod $eventSourcingHandler, Message $message): void
    {
        if (count($eventSourcingHandlerInterface->getInterfaceParameters()) > 1) {
            throw InvalidArgumentException::create("Using multiple parameters for Event Sourcing Handler: {$eventSourcingHandlerInterface} is part of Enterprise features. To use this feature obtain Enterprise.");
        }

        $aggregate->{$eventSourcingHandlerInterface->getMethodName()}($message->getPayload());
    }
}
