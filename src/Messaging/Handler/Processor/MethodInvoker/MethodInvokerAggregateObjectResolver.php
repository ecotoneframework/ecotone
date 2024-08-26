<?php
/*
 * licence Apache-2.0
 */

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Message;
use Ecotone\Modelling\AggregateMessage;

class MethodInvokerAggregateObjectResolver implements MethodInvokerObjectResolver
{
    public function resolveFor(Message $message): object
    {
        return $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT);
    }
}
