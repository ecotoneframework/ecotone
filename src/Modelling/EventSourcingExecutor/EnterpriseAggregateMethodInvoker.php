<?php

declare(strict_types=1);

namespace Ecotone\Modelling\EventSourcingExecutor;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Message;
use Ecotone\Modelling\EventSourcingHandlerMethod;

/**
 * licence Enterprise
 */
final class EnterpriseAggregateMethodInvoker implements AggregateMethodInvoker
{
    public function executeMethod(mixed $aggregate, InterfaceToCall $eventSourcingHandlerInterface, EventSourcingHandlerMethod $eventSourcingHandler, Message $message): void
    {
        (new MethodInvoker(
            $aggregate,
            $eventSourcingHandlerInterface->getMethodName(),
            $eventSourcingHandler->getParameterConverters(),
            $eventSourcingHandlerInterface->getInterfaceParametersNames(),
        ))->executeEndpoint($message);
    }
}
