<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\CompilableBuilder;

/**
 * Interface MessageChannelBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageChannelBuilder extends CompilableBuilder
{
    /**
     * @return string
     */
    public function getMessageChannelName(): string;

    /**
     * @return bool
     */
    public function isPollable(): bool;

    /**
     * Whatever messages are preserved after consumption, and be consumed by multiple consumer groups
     * @return string
     */
    public function isStreamingChannel(): bool;
}
