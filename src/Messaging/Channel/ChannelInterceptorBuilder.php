<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\CompilableBuilder;

/**
 * Interface ChannelInterceptorBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ChannelInterceptorBuilder extends CompilableBuilder
{
    /**
     * @return string
     */
    public function relatedChannelName(): string;

    /**
     * @return int
     */
    public function getPrecedence(): int;
}
