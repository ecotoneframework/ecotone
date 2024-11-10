<?php

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;

/**
 * licence Apache-2.0
 */
class NullEntrypointGateway implements InboundChannelAdapterEntrypoint, DefinedObject
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

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
