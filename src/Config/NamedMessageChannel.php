<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Class ResolvableChannel
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class NamedMessageChannel
{
    /**
     * @var string
     */
    private $channelName;
    /**
     * @var MessageChannel
     */
    private $messageChannel;

    /**
     * ResolvableChannel constructor.
     * @param string $channelName
     * @param MessageChannel $messageChannel
     */
    private function __construct(string $channelName, MessageChannel $messageChannel)
    {
        $this->channelName = $channelName;
        $this->messageChannel = $messageChannel;
    }

    /**
     * @param string $channelName
     * @param MessageChannel $messageChannel
     * @return NamedMessageChannel
     */
    public static function create(string $channelName, MessageChannel $messageChannel) : self
    {
        return new self($channelName, $messageChannel);
    }

    /**
     * @return MessageChannel
     */
    public function getMessageChannel() : MessageChannel
    {
        return $this->messageChannel;
    }

    /**
     * @param string|null $channelName
     * @return bool
     */
    public function hasName(?string $channelName) : bool
    {
        return $this->channelName == $channelName;
    }
}