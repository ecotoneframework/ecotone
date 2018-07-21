<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

/**
 * Interface MessageHandlerBuilderWithOutputChannel
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandlerBuilderWithOutputChannel extends MessageHandlerBuilder
{
    /**
     * @param string $messageChannelName
     *
     * @return static
     */
    public function withOutputMessageChannel(string $messageChannelName);
}