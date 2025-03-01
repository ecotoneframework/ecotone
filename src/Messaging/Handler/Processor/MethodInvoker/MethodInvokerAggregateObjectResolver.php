<?php

/*
 * licence Apache-2.0
 */

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Message;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\ResolvedAggregate;
use Ecotone\Modelling\AggregateMessage;

class MethodInvokerAggregateObjectResolver implements MethodInvokerObjectResolver
{
    public function resolveFor(Message $message): object
    {
        $aggregate = $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_INSTANCE);
        if ($aggregate instanceof ResolvedAggregate) {
            return $aggregate->getAggregateInstance();
        } else {
            return $aggregate;
        }
    }
}
