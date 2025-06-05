<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Ecotone\Messaging\Config\Configuration;

interface RoutingEventHandler
{
    public function handleRoutingEvent(RoutingEvent $event, ?Configuration $messagingConfiguration = null): void;
}
