<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\AsyncPublishing;

use Ecotone\Messaging\Message;

/**
 * licence Enterprise
 */
final class FailedDelivery
{
    public function __construct(
        private Message $message,
        private string $failureReason,
        private string $channelName,
    ) {
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getFailureReason(): string
    {
        return $this->failureReason;
    }
}
