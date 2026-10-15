<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\ReceivingStrategy;

use Ecotone\Messaging\Channel\DynamicChannel\ChannelReceivingStrategy;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class SkippingReceivingStrategy implements ChannelReceivingStrategy
{
    public function __construct(
        private MessagingEntrypointService   $messagingEntrypoint,
        private string                      $dynamicChannelName,
        private RoundRobinReceivingStrategy $robinReceivingStrategy,
        private string $channelNameToDecideOnTheConsumption
    ) {
    }

    public function decide(): string
    {
        $decidedChannel = $this->robinReceivingStrategy->decide();

        $shouldPoll = $this->messagingEntrypoint->sendWithHeaders(
            $decidedChannel,
            [
                'dynamicChannelName' => $this->dynamicChannelName,
            ],
            $this->channelNameToDecideOnTheConsumption
        );
        Assert::isTrue(is_bool($shouldPoll), 'Result decision should be boolean');

        if ($shouldPoll) {
            return $decidedChannel;
        }

        return NullableMessageChannel::CHANNEL_NAME;
    }
}
