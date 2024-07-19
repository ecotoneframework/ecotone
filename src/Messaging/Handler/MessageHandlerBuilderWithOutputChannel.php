<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

/**
 * Interface MessageHandlerBuilderWithOutputChannel
 * @package Ecotone\Messaging\Handler
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageHandlerBuilderWithOutputChannel extends MessageHandlerBuilder, InterceptedEndpoint
{
    /**
     * @param string $messageChannelName
     *
     * @return static
     */
    public function withOutputMessageChannel(string $messageChannelName);

    /**
     * @return string
     */
    public function getOutputMessageChannelName(): string;
}
