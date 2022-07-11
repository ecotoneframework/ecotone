<?php

namespace Ecotone\Enqueue;

use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;

class NullEntrypointGateway implements InboundChannelAdapterEntrypoint
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function executeEntrypoint($data): void
    {
    }
}
