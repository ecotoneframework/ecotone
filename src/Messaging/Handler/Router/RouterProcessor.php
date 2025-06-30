<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Message;
use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
class RouterProcessor implements MessageProcessor
{
    public function __construct(
        private RouteSelector $routeSelector,
        private RouteResolver $routeResolver,
        private bool $singleRoute = true,
    ) {
    }

    public function process(Message $message): ?Message
    {
        $routes = $this->routeSelector->route($message);

        if ($this->singleRoute) {
            if (count($routes) === 0) {
                return null;
            } elseif (count($routes) > 1) {
                throw new InvalidArgumentException('Expected only one route to be selected, but got more');
            }
            $path = $this->routeResolver->resolve($routes[0]);
            return $path->process($message);
        } else {
            foreach ($routes as $route) {
                $path = $this->routeResolver->resolve($route);
                $path->process($message);
            }
            return null;
        }
    }
}
