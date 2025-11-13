<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel;

use Ecotone\Messaging\Channel\DynamicChannel\ReceivingStrategy\CustomReceivingStrategy;
use Ecotone\Messaging\Channel\DynamicChannel\ReceivingStrategy\NoReceivingStrategy;
use Ecotone\Messaging\Channel\DynamicChannel\ReceivingStrategy\RoundRobinReceivingStrategy;
use Ecotone\Messaging\Channel\DynamicChannel\ReceivingStrategy\SkippingReceivingStrategy;
use Ecotone\Messaging\Channel\DynamicChannel\SendingStrategy\CustomSendingStrategy;
use Ecotone\Messaging\Channel\DynamicChannel\SendingStrategy\HeaderSendingStrategy;
use Ecotone\Messaging\Channel\DynamicChannel\SendingStrategy\NoSendingStrategy;
use Ecotone\Messaging\Channel\DynamicChannel\SendingStrategy\RoundRobinSendingStrategy;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Enterprise
 */
final class DynamicMessageChannelBuilder implements MessageChannelBuilder
{
    /**
     * @param MessageChannelBuilder[] $internalMessageChannels
     */
    private function __construct(
        private string     $thisMessageChannelName,
        private Definition $channelSendingStrategy,
        private Definition $channelReceivingStrategy,
        private array      $internalMessageChannels = []
    ) {
        Assert::allInstanceOfType($internalMessageChannels, MessageChannelBuilder::class);
    }

    /**
     * Creates with default round robin strategy for sending and receiving
     *
     * @param string[] $receivingChannelNames
     * @param string[] $sendingChannelNames
     * @param MessageChannelBuilder[] $internalMessageChannels
     */
    public static function createRoundRobinWithDifferentChannels(
        string $thisMessageChannelName,
        array $sendingChannelNames,
        array $receivingChannelNames,
    ): self {
        return new self(
            $thisMessageChannelName,
            new Definition(RoundRobinSendingStrategy::class, [$sendingChannelNames]),
            new Definition(RoundRobinReceivingStrategy::class, [$receivingChannelNames]),
        );
    }

    public function hasReceiveStrategy(): bool
    {
        return ! ($this->channelReceivingStrategy->getClassName() === NoReceivingStrategy::class);
    }

    /**
     * Creates with default round robin strategy for sending and receiving
     *
     * @param string[] $channelNames
     * @param MessageChannelBuilder[] $internalMessageChannels
     */
    public static function createRoundRobin(
        string $thisMessageChannelName,
        array $channelNames = [],
    ): self {
        return new self(
            $thisMessageChannelName,
            new Definition(RoundRobinSendingStrategy::class, [$channelNames]),
            new Definition(RoundRobinReceivingStrategy::class, [$channelNames]),
        );
    }

    /**
     * This will create dummy channel that by default will do nothing.
     * Therefore can be used for customization
     */
    public static function createNoStrategy(string $thisMessageChannelName): self
    {
        return new self(
            $thisMessageChannelName,
            new Definition(NoSendingStrategy::class, [$thisMessageChannelName]),
            new Definition(NoReceivingStrategy::class, [$thisMessageChannelName]),
        );
    }

    public static function createWithSendOnlyStrategy(MessageChannelBuilder $targetMessageChannel): self
    {
        return (new self(
            $targetMessageChannel->getMessageChannelName(),
            new Definition(RoundRobinSendingStrategy::class, [[$targetMessageChannel->getMessageChannelName()]]),
            new Definition(NoReceivingStrategy::class, [$targetMessageChannel->getMessageChannelName()]),
        ))->withInternalChannels([
            $targetMessageChannel->getMessageChannelName() => $targetMessageChannel,
        ]);
    }

    /**
     * This make use of HeaderSendingStrategy for sending and round robin for receiving
     *
     * @param string $headerName Name of the header that will be used to decide on channel name
     * @param string[] $headerMapping Mapping of header value to channel name. If null header value wil be taken as channel name
     * @param string|null $defaultChannelName Name of the channel that will be used if no mapping is found. If null Exception will be thrown.
     */
    public static function createWithHeaderBasedStrategy(
        string $thisMessageChannelName,
        string $headerName,
        array $headerMapping,
        ?string $defaultChannelName = null,
    ): self {
        return new self(
            $thisMessageChannelName,
            new Definition(HeaderSendingStrategy::class, [
                $headerName,
                $headerMapping,
                $defaultChannelName,
            ]),
            new Definition(RoundRobinReceivingStrategy::class, [array_unique(array_merge(array_values($headerMapping)))]),
        );
    }

    /**
     * This make use of throttling strategy for consumption.
     * If used for sending it will use round robin strategy
     *
     * @param string $requestChannelName Name of the inputChannel of Internal Message Handler that will decide on the consumption
     * @param string[] $channelNames
     */
    public static function createThrottlingStrategy(
        string $thisMessageChannelName,
        string $requestChannelName,
        array $channelNames = [],
    ): self {
        return new self(
            $thisMessageChannelName,
            new Definition(RoundRobinSendingStrategy::class, [$channelNames]),
            new Definition(SkippingReceivingStrategy::class, [
                Reference::to(MessagingEntrypoint::class),
                $thisMessageChannelName,
                new Definition(RoundRobinReceivingStrategy::class, [$channelNames]),
                $requestChannelName,
            ]),
        );
    }

    /**
     * @param MessageChannelBuilder[] $internalMessageChannels
     */
    public function withInternalChannels(array $internalMessageChannels): self
    {
        Assert::allInstanceOfType($internalMessageChannels, MessageChannelBuilder::class);

        $this->internalMessageChannels = $internalMessageChannels;

        return $this;
    }

    /**
     * @param string $requestChannelName Name of the inputChannel of Internal Message Handler that will provide channel name to send message to
     */
    public function withCustomSendingStrategy(string $requestChannelName): self
    {
        $this->channelSendingStrategy = new Definition(CustomSendingStrategy::class, [
            Reference::to(MessagingEntrypoint::class),
            $requestChannelName,
        ]);

        return $this;
    }

    /**
     * @param string $headerName Name of the header that will be used to decide on channel name
     * @param string[]|null $headerMapping Mapping of header value to channel name. If null header value wil be taken as channel name
     * @param string|null $defaultChannelName Name of the channel that will be used if no mapping is found. If null Exception will be thrown.
     */
    public function withHeaderSendingStrategy(string $headerName, ?array $headerMapping = null, ?string $defaultChannelName = null): self
    {
        $this->channelSendingStrategy = new Definition(HeaderSendingStrategy::class, [
            $headerName,
            $headerMapping,
            $defaultChannelName,
        ]);

        return $this;
    }

    /**
     * @param string $requestChannelName Name of the inputChannel of Internal Message Handler that will provide channel name to poll message from
     */
    public function withCustomReceivingStrategy(string $requestChannelName): self
    {
        $this->channelReceivingStrategy = new Definition(CustomReceivingStrategy::class, [
            Reference::to(MessagingEntrypoint::class),
            $requestChannelName,
        ]);

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        if (! $builder->has(InternalChannelResolver::class)) {
            $builder->register(
                InternalChannelResolver::class,
                new Definition(
                    InternalChannelResolver::class,
                    [
                        Reference::to(ChannelResolver::class),
                        array_map(
                            fn (MessageChannelBuilder $channelBuilder, $key) => ['channel' => $channelBuilder->compile($builder), 'name' => is_int($key) ? $channelBuilder->getMessageChannelName() : $key],
                            $this->internalMessageChannels,
                            array_keys($this->internalMessageChannels)
                        ),
                    ]
                )
            );
        }

        return new Definition(
            DynamicMessageChannel::class,
            [
                $this->thisMessageChannelName,
                Reference::to(InternalChannelResolver::class),
                $this->channelSendingStrategy,
                $this->channelReceivingStrategy,
                Reference::to(LoggingGateway::class),
            ]
        );
    }

    public function getMessageChannelName(): string
    {
        return $this->thisMessageChannelName;
    }

    public function isPollable(): bool
    {
        return true;
    }

    public function isStreamingChannel(): bool
    {
        return false;
    }
}
