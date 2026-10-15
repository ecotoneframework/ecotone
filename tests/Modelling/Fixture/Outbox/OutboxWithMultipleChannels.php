<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Outbox;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\CombinedMessageChannel;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;

/**
 * licence Apache-2.0
 */
final class OutboxWithMultipleChannels
{
    private int $amount = 0;

    #[Asynchronous(['outbox', 'rabbitMQ'])]
    #[CommandHandler('outboxWithMultipleChannels', endpointId: 'outboxMultipleChannelsId')]
    public function handle(int $amount): void
    {
        $this->amount = $amount;
    }

    #[Asynchronous(['outbox_rabbit'])]
    #[CommandHandler('outboxWithCombinedChannels', endpointId: 'outboxCombinedMessageChannelsId')]
    public function handleWithCombinedMessageChannel(int $amount): void
    {
        $this->amount = $amount;
    }

    #[QueryHandler('getResult')]
    public function getResult(): int
    {
        return $this->amount;
    }

    #[ServiceContext]
    public function getChannels()
    {
        /** faking asynchronous message channels */
        return [
            SimpleMessageChannelBuilder::createQueueChannel('outbox'),
            PollingMetadata::create('outbox')->withTestingSetup(),
            SimpleMessageChannelBuilder::createQueueChannel('rabbitMQ'),
            PollingMetadata::create('rabbitMQ')->withTestingSetup(),
        ];
    }

    #[ServiceContext]
    public function combinedMessageChannel(): CombinedMessageChannel
    {
        return CombinedMessageChannel::create(
            'outbox_rabbit',
            ['outbox', 'rabbitMQ'],
        );
    }
}
