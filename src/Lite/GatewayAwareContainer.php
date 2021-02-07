<?php

namespace Ecotone\Lite;

use Psr\Container\ContainerInterface;

interface GatewayAwareContainer extends ContainerInterface
{
    public function addGateway(string $referenceName, object $gateway) : void;
}