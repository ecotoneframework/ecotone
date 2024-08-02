<?php

declare(strict_types=1);

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\Messaging\Message;
use Ecotone\Modelling\EventSourcingHandlerMethod;

/**
 * licence Apache-2.0
 */
interface AggregateMethodInvoker
{
    public function executeMethod(mixed $aggregate, EventSourcingHandlerMethod $eventSourcingHandler, Message $message): void;
}
