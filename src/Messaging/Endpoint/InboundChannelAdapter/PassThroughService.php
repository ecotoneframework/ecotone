<?php

namespace Ecotone\Messaging\Endpoint\InboundChannelAdapter;

use DateTimeImmutable;

/**
 * licence Apache-2.0
 */
class PassThroughService
{
    private object $serviceToCall;
    private string $method;

    public function __construct(object $serviceToCall, string $method)
    {
        $this->serviceToCall = $serviceToCall;
        $this->method = $method;
    }

    public function execute(): DateTimeImmutable
    {
        $this->serviceToCall->{$this->method}();

        return new DateTimeImmutable();
    }
}
