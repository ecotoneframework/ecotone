<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Router\RouteSelector;

class CommandBusRouteSelector implements RouteSelector
{
    public function __construct(private BusRoutingMap $busRoutingConfig, private BusRoutingKeyResolver $routingKeyResolver, private LoggingGateway $loggingGateway, private bool $isRunningForFlowTesting = false)
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
            throw DestinationResolutionException::create($this->isRunningForFlowTesting
                ? MissingHandlerMessage::forFlowTesting('command', $routingKey, CommandHandler::class)
                : "Can't send command to {$routingKey}. No Command Handler defined for it. Have you forgot to add #[CommandHandler] to method?");
        } else {
            $this->loggingGateway->info(sprintf('Sending Command Message with: %s.', $routingKey), $message);
        }

        return $routes;
    }

    public function __toString(): string
    {
        return 'command bus routing';
    }
}
