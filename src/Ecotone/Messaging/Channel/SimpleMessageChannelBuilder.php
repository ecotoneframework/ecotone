<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\PollableChannel;

/**
 * Class SimpleMessageChannelBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleMessageChannelBuilder implements MessageChannelBuilder
{
    private string $messageChannelName;
    private \Ecotone\Messaging\MessageChannel $messageChannel;
    private bool $isPollable;

    /**
     * SimpleMessageChannelBuilder constructor.
     * @param string $messageChannelName
     * @param MessageChannel $messageChannel
     * @param bool $isPollable
     */
    private function __construct(string $messageChannelName, MessageChannel $messageChannel, bool $isPollable)
    {
        $this->messageChannelName = $messageChannelName;
        $this->messageChannel = $messageChannel;
        $this->isPollable = $isPollable;
    }

    public static function create(string $messageChannelName, MessageChannel $messageChannel) : self
    {
        return new self($messageChannelName, $messageChannel, $messageChannel instanceof PollableChannel);
    }

    public static function createDirectMessageChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, DirectChannel::create());
    }

    public static function createPublishSubscribeChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, PublishSubscribeChannel::create());
    }

    public static function createQueueChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, QueueChannel::create());
    }

    public static function createNullableChannel(string $messageChannelName) : self
    {
        return self::create($messageChannelName, NullableMessageChannel::create());
    }

    /**
     * @inheritDoc
     */
    public function isPollable(): bool
    {
        return $this->isPollable;
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
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
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