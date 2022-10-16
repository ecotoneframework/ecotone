<?php

namespace Ecotone\Lite;

use Psr\Container\ContainerInterface;

/**
 * @TOOD ecotone 2.0 to throw away
 */
interface GatewayAwareContainer extends ContainerInterface
{
    public function addGateway(string $referenceName, object $gateway): void;
}
