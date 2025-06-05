<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Router\RouteSelector;
use Ecotone\Messaging\Message;

class QueryBusRouteSelector implements RouteSelector
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

        if (empty($routes)) {
            throw DestinationResolutionException::create("Can't send query to {$routingKey}. No Query Handler defined for it. Have you forgot to add #[QueryHandler] to method?");
        } else {
            $this->loggingGateway->info(sprintf('Sending Query Message with: %s.', $routingKey), $message);
        }

        return $routes;
    }
}
