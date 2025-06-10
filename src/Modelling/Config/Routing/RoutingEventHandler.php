<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

interface RoutingEventHandler
{
    public function handleRoutingEvent(RoutingEvent $event): void;
}
