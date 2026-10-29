<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Poller;

use Ecotone\Messaging\Attribute\Poller;
use Ecotone\Messaging\Attribute\Scheduled;
use Ecotone\Messaging\NullableMessageChannel;

/**
 * licence Apache-2.0
 */
final class ExpressionPollerExample
{
    private array $processedMessages = [];

    #[Scheduled(NullableMessageChannel::CHANNEL_NAME, 'expression_poller_endpoint')]
    #[Poller(fixedRateExpression: "reference('timerService').getFixedRate()")]
    public function pollWithFixedRateExpression(): void
    {
        $this->processedMessages[] = 'fixed_rate_message';
    }

    #[Scheduled(NullableMessageChannel::CHANNEL_NAME, 'cron_expression_poller_endpoint')]
    #[Poller(cronExpression: "reference('timerService').getCronSchedule()")]
    public function pollWithCronExpression(): void
    {
        $this->processedMessages[] = 'cron_message';
    }

    public function getProcessedMessages(): array
    {
        return $this->processedMessages;
    }

    public function reset(): void
    {
        $this->processedMessages = [];
    }
}
