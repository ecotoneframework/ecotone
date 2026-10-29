<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Ecotone\Messaging\Handler\Router\RouteSelector;
use Ecotone\Messaging\Message;

class BusRouteSelector implements RouteSelector
{
    public function __construct(private BusRoutingMap $busRoutingConfig, private BusRoutingKeyResolver $routingKeyResolver)
    {
    }

    /**
     * @inheritDoc
     */
    final public function route(Message $message): array
    {
        $routingKey = $this->routingKeyResolver->resolve($message);

        return $this->busRoutingConfig->get($routingKey);
    }
}
