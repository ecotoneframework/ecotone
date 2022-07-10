<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

/**
 * Interface MessageHandlerBuilderWithOutputChannel
 * @package Ecotone\Messaging\Handler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
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
    public function getOutputMessageChannelName() : string;
}