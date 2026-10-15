<?php

namespace Ecotone\Api\ExtensionObject;

use Ecotone\Messaging\Config\Container\CompilableBuilder;

/**
 * licence Apache-2.0
 */
interface MessageChannelBuilder extends CompilableBuilder
{
    public function getMessageChannelName(): string;

    public function isPollable(): bool;

    public function isStreamingChannel(): bool;
}
