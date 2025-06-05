<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Router\RouteSelector;
use Ecotone\Messaging\Message;

class EventBusRouteSelector implements RouteSelector
{
    public function __construct(private BusRoutingMap $busRoutingConfig, private BusRoutingKeyResolver $routingKeyResolver, private LoggingGateway $loggingGateway)
    {
    }

    /**
     * @inheritDoc
     */
    final public function route(Message $message): array
    {
        $routingKey = $this->routingKeyResolver->resolve($message);

        $routes = $this->busRoutingConfig->get($routingKey);

        $this->loggingGateway->info(
            sprintf('Publishing Event Message using: %s.', $routingKey),
            $message,
            ['resolvedChannels' => $routes]
        );

        return $routes;
    }
}
