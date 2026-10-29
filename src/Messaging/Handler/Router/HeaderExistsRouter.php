<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;

/**
 * Class HeaderValueRouter
 * @package Ecotone\Messaging\Handler\Router
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
final class HeaderExistsRouter implements RouteSelector, DefinedObject
{
    private function __construct(private string $headerName, private string $routeToChannel, private string $fallbackRoute)
    {
    }

    public static function create(string $headerName, string $routeToChannel, string $fallbackRoute): self
    {
        return new self($headerName, $routeToChannel, $fallbackRoute);
    }

    /**
     * @param Message $message
     * @return array
     */
    public function route(Message $message): array
    {
        return $message->getHeaders()->containsKey($this->headerName)
            ? [$this->routeToChannel]
            : [$this->fallbackRoute];
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->headerName,
            $this->routeToChannel,
            $this->fallbackRoute,
        ], 'create');
    }
}
