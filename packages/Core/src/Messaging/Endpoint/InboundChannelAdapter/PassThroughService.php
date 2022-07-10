<?php

namespace Ecotone\Messaging\Endpoint\InboundChannelAdapter;

class PassThroughService
{
    private object $serviceToCall;
    private string $method;

    public function __construct(object $serviceToCall, string $method)
    {
        $this->serviceToCall = $serviceToCall;
        $this->method = $method;
    }

    public function execute(): \DateTimeImmutable
    {
        call_user_func_array([$this->serviceToCall, $this->method], []);

        return new \DateTimeImmutable();
    }
}