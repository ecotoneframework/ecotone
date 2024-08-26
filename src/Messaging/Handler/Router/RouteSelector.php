<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface RouteSelector
{
    /**
     * @param Message $message
     * @return string[]
     */
    public function route(Message $message): array;
}
