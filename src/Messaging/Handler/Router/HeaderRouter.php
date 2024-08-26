<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
class HeaderRouter implements RouteSelector
{
    private string $headerName;

    public function __construct(string $headerName)
    {
        $this->headerName = $headerName;
    }

    public static function create(string $headerName): self
    {
        return new self($headerName);
    }

    public function route(Message $message): array
    {
        return [$message->getHeaders()->get($this->headerName)];
    }
}
