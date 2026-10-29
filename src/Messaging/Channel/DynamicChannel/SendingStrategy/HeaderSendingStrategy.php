<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\DynamicChannel\SendingStrategy;

use Ecotone\Messaging\Channel\DynamicChannel\ChannelSendingStrategy;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Enterprise
 */
final class HeaderSendingStrategy implements ChannelSendingStrategy
{
    public function __construct(
        private string $headerName,
        private ?array $headerMapping,
        private ?string $defaultChannelName
    ) {

    }

    public function decideFor(Message $message): string
    {
        $headerValue = $message->getHeaders()->containsKey($this->headerName)
            ? $message->getHeaders()->get($this->headerName)
            : null;

        if ($headerValue === null) {
            if ($this->defaultChannelName) {
                return $this->defaultChannelName;
            }

            throw new InvalidArgumentException("Header {$this->headerName} must be present for Header Sending Strategy.");
        }

        if ($this->headerMapping === null) {
            return $headerValue;
        }

        if (! array_key_exists($headerValue, $this->headerMapping)) {
            throw new InvalidArgumentException("Header {$this->headerName} must be one of " . implode(', ', array_keys($this->headerMapping)));
        }

        return $this->headerMapping[$headerValue];
    }
}
