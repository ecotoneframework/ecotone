<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Outbox;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Channel\CombinedMessageChannel;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

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
            SimpleMessageChannelBuilder::createQueueChannel("outbox"),
            PollingMetadata::create('outbox')->withTestingSetup(),
            SimpleMessageChannelBuilder::createQueueChannel("rabbitMQ"),
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