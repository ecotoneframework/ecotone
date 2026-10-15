<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Api\Messaging\Message;
use Stringable;

/**
 * licence Apache-2.0
 */
interface RouteSelector extends Stringable
{
    /**
     * @param Message $message
     * @return string[]
     */
    public function route(Message $message): array;
}
