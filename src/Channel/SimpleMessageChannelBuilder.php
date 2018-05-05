<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Channel;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;

/**
 * Class SimpleMessageChannelBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleMessageChannelBuilder implements MessageChannelBuilder
{
    /**
     * @var string
     */
    private $messageChannelName;
    /**
     * @var MessageChannel
     */
    private $messageChannel;

    /**
     * SimpleMessageChannelBuilder constructor.
     * @param string $messageChannelName
     * @param MessageChannel $messageChannel
     */
    private function __construct(string $messageChannelName, MessageChannel $messageChannel)
    {
        $this->messageChannelName = $messageChannelName;
        $this->messageChannel = $messageChannel;
    }

    /**
     * @param string $messageChannelName
     * @param MessageChannel $messageChannel
     * @return SimpleMessageChannelBuilder
     */
    public static function create(string $messageChannelName, MessageChannel $messageChannel) : self
    {
        return new self($messageChannelName, $messageChannel);
    }

    /**
     * @param string $messageChannelName
     * @return SimpleMessageChannelBuilder
     */
    public static function createDirectMessageChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, DirectChannel::create());
    }

    /**
     * @param string $messageChannelName
     * @return SimpleMessageChannelBuilder
     */
    public static function createPublishSubscribeChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, PublishSubscribeChannel::create());
    }

    /**
     * @param string $messageChannelName
     * @return SimpleMessageChannelBuilder
     */
    public static function createQueueChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, QueueChannel::create());
    }

    /**
     * @return string[] empty string means no required reference name exists
     */
    public function getRequiredReferenceNames() : array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getMessageChannelName(): string
    {
        return $this->messageChannelName;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService) : MessageChannel
    {
        return $this->messageChannel;
    }

    public function __toString()
    {
        return (string)$this->messageChannel;
    }
}