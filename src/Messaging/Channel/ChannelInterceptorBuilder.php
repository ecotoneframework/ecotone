<?php

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\CompilableBuilder;

/**
 * Interface ChannelInterceptorBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
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
