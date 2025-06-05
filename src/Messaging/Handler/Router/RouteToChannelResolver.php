<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Processor\SendToChannelProcessor;

class RouteToChannelResolver implements RouteResolver
{
    public function __construct(
        public ChannelResolver $channelResolver
    ) {
    }

    public function resolve(string $routeName): MessageProcessor
    {
        $channel = $this->channelResolver->resolve($routeName);
        return new SendToChannelProcessor($channel);
    }
}
