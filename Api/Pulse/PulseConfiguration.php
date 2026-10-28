<?php

declare(strict_types=1);

namespace Ecotone\Api\Pulse;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class PulseConfiguration
{
    private function __construct(public readonly string $pulseServiceName, public readonly ?string $replyChannel = null, public readonly ?string $requestChannel = null)
    {
    }

    public static function createWithDefaults(string $pulseServiceName): self
    {
        return new self($pulseServiceName);
    }

    public function withReplyChannel(string $channelName): self
    {
        return new self(...[...get_object_vars($this), 'replyChannel' => $channelName]);
    }

    public function withRequestChannel(string $channelName): self
    {
        return new self(...[...get_object_vars($this), 'requestChannel' => $channelName]);
    }
}
