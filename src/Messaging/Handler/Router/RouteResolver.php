<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Handler\MessageProcessor;

/**
 * licence Apache-2.0
 */
interface RouteResolver
{
    public function resolve(string $routeName): MessageProcessor;
}
