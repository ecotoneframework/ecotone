<?php

namespace Ecotone\Messaging\Endpoint\InboundChannelAdapter;

use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Scheduling\DatePoint;

/**
 * licence Apache-2.0
 */
class PassThroughService
{
    public function __construct(
        private object $serviceToCall,
        private string $method,
        private EcotoneClockInterface $clock,
    ) {
    }

    public function execute(): DatePoint
    {
        $this->serviceToCall->{$this->method}();

        return $this->clock->now();
    }
}
