<?php

namespace Ecotone\Messaging\Handler\Router;

use function array_unique;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;

use function is_iterable;

/**
 * licence Apache-2.0
 */
class InvocationRouter implements RouteSelector
{
    public function __construct(private MethodInvoker $methodInvoker, private string $routingMethod)
    {
    }

    public function route(Message $message): array
    {
        $result = $this->methodInvoker->execute($message);
        if (! is_iterable($result)) {
            $result = [$result];
        }
        return array_unique($result);
    }

    public function __toString(): string
    {
        return 'invocation of ' . $this->routingMethod;
    }
}
