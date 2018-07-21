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

    /**
     * @param MessageHandlerBuilderWithOutputChannel[] $preCallInterceptors
     *
     * @return static
     */
    public function withPreCallInterceptors(array $preCallInterceptors);

    /**
     * @param MessageHandlerBuilderWithOutputChannel[] $postCallInterceptors
     *
     * @return static
     */
    public function withPostCallInterceptors(array $postCallInterceptors);

    /**
     * @return MessageHandlerBuilderWithOutputChannel[]
     */
    public function getPreCallInterceptors() : array;

    /**
     * @return MessageHandlerBuilderWithOutputChannel[]
     */
    public function getPostCallInterceptors() : array;
}